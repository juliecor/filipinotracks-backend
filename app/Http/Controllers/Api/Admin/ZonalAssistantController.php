<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZonalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scope-locked AI assistant for the Title Scanner. Answers ONLY questions about
 * zonal value, market value, land classification, transfer taxes, and the
 * scanned lot — grounded in the data passed as context. Can call a tool to look
 * up zonal values for a different location on demand.
 *
 * Endpoint: POST /api/zonal-assistant   (sanctum + admin/staff)
 */
class ZonalAssistantController extends Controller
{
    private const MODEL    = 'gpt-4o-mini';
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const SYSTEM = <<<'TXT'
You are the FilipinoTracks Zonal Assistant, embedded in an AI land-title scanner.

You help with Philippine real estate / property topics:
- BIR zonal values and land classifications (Residential, Commercial, Agricultural, etc.)
- the estimated market value and comparable listings
- Philippine property transfer taxes & fees (Capital Gains Tax, Documentary Stamp Tax, Transfer Tax, Registration Fee)
- a property's title details, area, and valuation
- SUITABLE LAND/BUSINESS USE for a lot given its classification and location
  (e.g. a Commercial-classified roadside lot suits retail/office; Agricultural suits farming)
- practical real-estate & business guidance for a property: what could be built or operated there, rough
  investment considerations, comparing zonal vs market value, and which use tends to fit the area —
  always framed as general, non-binding guidance (not financial or legal advice)

If asked something clearly unrelated to real estate / property (e.g. coding, celebrities, recipes), politely decline:
"I can only help with property, valuation, land use, taxes, and title questions."

Rules:
- Use ONLY the figures in the provided context or returned by the lookup tool. Never invent values.
- If a figure isn't available, say so plainly.
- You CANNOT assess real-world safety, crime, flooding, or guarantee a use is allowed — if asked, say it must be verified
  with the LGU/zoning office; you can only speak to the BIR land classification.
- All amounts are Philippine pesos (₱). BIR taxes the HIGHER of the zonal value or the actual selling price.
- Be concise and clear. Use short answers; show the math when helpful.
- To answer about a DIFFERENT location than the current lot, call the lookup_zonal_value tool.
- These are indicative estimates, not a formal appraisal.
TXT;

    public function chat(Request $request, ZonalService $zonal)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'messages'             => 'required|array|min:1|max:20',
            'messages.*.role'      => 'required|string|in:user,assistant',
            'messages.*.content'   => 'required|string|max:2000',
            'context'              => 'nullable|array',
        ]);

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['message' => 'OpenAI is not configured.'], 503);
        }

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM],
            ['role' => 'system', 'content' => 'CURRENT LOT CONTEXT (JSON):' . "\n" . json_encode($data['context'] ?? null)],
        ];
        foreach ($data['messages'] as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $tools = [[
            'type' => 'function',
            'function' => [
                'name' => 'lookup_zonal_value',
                'description' => 'Look up official BIR zonal values for a Philippine location. Use when the user asks about a location other than the current lot.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'province' => ['type' => 'string', 'description' => 'Province name, e.g. "Cebu"'],
                        'city'     => ['type' => 'string', 'description' => 'City/municipality (optional)'],
                        'barangay' => ['type' => 'string', 'description' => 'Barangay (optional but recommended)'],
                    ],
                    'required' => ['province'],
                ],
            ],
        ]];

        try {
            // Tool-calling loop (cap a few rounds)
            for ($round = 0; $round < 4; $round++) {
                $resp = Http::withToken($apiKey)->timeout(60)->acceptJson()->post(self::ENDPOINT, [
                    'model'       => self::MODEL,
                    'messages'    => $messages,
                    'tools'       => $tools,
                    'temperature' => 0.2,
                    'max_tokens'  => 700,
                ]);

                if (!$resp->ok()) {
                    $detail = $resp->json('error.message') ?: $resp->body();
                    Log::warning('Assistant OpenAI non-2xx', ['status' => $resp->status(), 'detail' => $detail]);
                    return response()->json(['message' => 'Assistant error: ' . substr((string) $detail, 0, 240)], 502);
                }

                $msg = $resp->json('choices.0.message');
                $toolCalls = $msg['tool_calls'] ?? [];

                if (empty($toolCalls)) {
                    return response()->json(['reply' => trim($msg['content'] ?? '')]);
                }

                // Append the assistant's tool-call message, then each tool result
                $messages[] = $msg;
                foreach ($toolCalls as $tc) {
                    $args = json_decode($tc['function']['arguments'] ?? '{}', true) ?: [];
                    $result = ['error' => 'unknown tool'];
                    if (($tc['function']['name'] ?? '') === 'lookup_zonal_value') {
                        $lookup = $zonal->lookup($args['province'] ?? '', $args['city'] ?? '', $args['barangay'] ?? '');
                        $result = $zonal->summarize($lookup);
                    }
                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => json_encode($result),
                    ];
                }
            }
            return response()->json(['reply' => 'I looked up a few things but couldn\'t finalize an answer — please rephrase.']);
        } catch (\Throwable $e) {
            Log::error('Assistant failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Assistant is unreachable right now.'], 504);
        }
    }
}
