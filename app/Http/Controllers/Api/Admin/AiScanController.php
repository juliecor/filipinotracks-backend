<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered Philippine land-title extraction.
 *
 * Proxies a structured GPT-4o Vision call so the OpenAI API key never
 * leaves the server. Replaces the prior frontend-direct integration
 * where the key shipped in the browser bundle.
 *
 * Endpoint: POST /api/admin/ai-scan-title
 * Auth:    sanctum + role:admin (or staff)
 */
class AiScanController extends Controller
{
    private const MODEL    = 'gpt-4o';
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * The full extraction prompt — kept identical to the frontend version
     * so we can flip the integration without changing observed behavior.
     */
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an expert Philippine land surveyor and title-examiner.

You will be shown an image of a land title document (Original Certificate of Title, Transfer Certificate of Title, Tax Declaration, or the surveyor's plan/technical description sheet).

Your job: extract the structured fields below as accurately as possible.

CRITICAL RULES:
1. Bearings on PH titles use the QUADRANT/SURVEYOR format:  N XX° YY' E  or  S XX° YY' W
   - First letter (dir1) is either "N" or "S"
   - The number XX is degrees (0–89)
   - YY is minutes (0–59); some titles also have seconds — ignore seconds (or include them as fractional minutes)
   - Last letter (dir2) is either "E" or "W"
   Example: "N 76°26' W" → dir1="N", degrees=76, minutes=26, dir2="W"
2. Distances are typically in METERS. Look for "m." or "meters". If you see "ft" or "feet", convert to meters.
3. Lot numbering usually follows "LOT 4519 GSS-07-02-000031" pattern — "4519" is lot_number, "GSS-07-02-000031" is survey_plan_number.
4. The TIE LINE is the line connecting the BLLM (control monument) to Corner 1. Capture as text if visible.
5. Land area is normally given as "sq. m." or "square meters" — extract as a plain number in sqm.
6. Return null for fields you genuinely can't find. Don't guess.
7. confidence:
   - "high"   — clean scan, all major fields read confidently
   - "medium" — readable but some ambiguity (faint scan, partial table, etc.)
   - "low"    — heavy distortion, missing fields, possibly wrong document type
8. The bearings array must list lines in ORDER from corner 1→2, 2→3, …, last→1.
   Each bearing's point_from / point_to should be the corner numbers as strings ("1", "2", …).
9. Rate EVERY top-level field individually in field_confidence:
   "high" = crisply legible, "medium" = readable but ambiguous, "low" = guessed from a faint/blurry region,
   "unknown" = the field is null / not present on the document.
10. Rate each bearing row's legibility in its "conf" field the same way ("high" / "medium" / "low").
PROMPT;

    /** The JSON schema OpenAI must conform to — identical to the frontend version. */
    private const RESPONSE_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
            'title_number'       => ['type' => ['string', 'null']],
            'lot_number'         => ['type' => ['string', 'null']],
            'block_number'       => ['type' => ['string', 'null']],
            'survey_plan_number' => ['type' => ['string', 'null']],
            'registered_owner'   => ['type' => ['string', 'null']],
            'land_area_sqm'      => ['type' => ['number', 'null']],
            'province'           => ['type' => ['string', 'null']],
            'city_municipality'  => ['type' => ['string', 'null']],
            'barangay'           => ['type' => ['string', 'null']],
            'full_address'       => ['type' => ['string', 'null']],
            'tie_line'           => ['type' => ['string', 'null']],
            'bearings' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'point_from' => ['type' => 'string'],
                        'point_to'   => ['type' => 'string'],
                        'dir1'       => ['type' => 'string', 'enum' => ['N', 'S']],
                        'degrees'    => ['type' => 'number'],
                        'minutes'    => ['type' => 'number'],
                        'dir2'       => ['type' => 'string', 'enum' => ['E', 'W']],
                        'distance'   => ['type' => 'number'],
                        'conf'       => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                    ],
                    'required' => ['point_from', 'point_to', 'dir1', 'degrees', 'minutes', 'dir2', 'distance', 'conf'],
                ],
            ],
            'field_confidence' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'title_number'       => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'lot_number'         => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'block_number'       => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'survey_plan_number' => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'registered_owner'   => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'land_area_sqm'      => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'province'           => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'city_municipality'  => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                    'barangay'           => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'unknown']],
                ],
                'required' => [
                    'title_number', 'lot_number', 'block_number', 'survey_plan_number',
                    'registered_owner', 'land_area_sqm',
                    'province', 'city_municipality', 'barangay',
                ],
            ],
            'confidence' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
            'notes'      => ['type' => ['string', 'null']],
        ],
        'required' => [
            'title_number', 'lot_number', 'block_number', 'survey_plan_number',
            'registered_owner', 'land_area_sqm',
            'province', 'city_municipality', 'barangay', 'full_address',
            'tie_line', 'bearings', 'field_confidence', 'confidence', 'notes',
        ],
    ];

    /**
     * Accept 1–2 title images, run them through GPT-4o Vision,
     * and return the structured extraction.
     */
    public function scanTitle(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'images'   => 'required|array|min:1|max:2',
            'images.*' => 'required|file|image|mimetypes:image/jpeg,image/png,image/webp|max:20480',
            'focus'    => 'sometimes|string|in:bearings',
        ], [
            'images.*.max' => 'Each image must be 20 MB or smaller.',
        ]);

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'message' => 'OpenAI is not configured. Set OPENAI_API_KEY in the backend .env file.',
            ], 503);
        }

        // Convert uploaded files to base64 data URLs for OpenAI's vision endpoint.
        $imageMessages = [];
        foreach ($request->file('images') as $file) {
            $mime    = $file->getMimeType() ?: 'image/jpeg';
            $base64  = base64_encode(file_get_contents($file->getRealPath()));
            $dataUrl = "data:{$mime};base64,{$base64}";
            $imageMessages[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => $dataUrl, 'detail' => 'high'],
            ];
        }

        $baseText = count($imageMessages) > 1
            ? 'Extract the title information from these ' . count($imageMessages) . ' images (they belong to the same property — front + back / page 1 + page 2).'
            : 'Extract the title information from this image.';

        // A focused re-scan asks the model to concentrate on the technical
        // description so a poorly-read bearings table can be recovered without
        // the user retyping every course by hand.
        if ($request->input('focus') === 'bearings') {
            $baseText .= ' FOCUS: Concentrate on the TECHNICAL DESCRIPTION / bearings table. '
                . 'Read EVERY course in order (1→2, 2→3, …, last→1) with precise dir1, degrees, minutes, dir2, and distance in meters. '
                . 'Be meticulous with the minutes column and do not skip or merge any line. Other fields may be approximate.';
        }

        $userContent = array_merge(
            [['type' => 'text', 'text' => $baseText]],
            $imageMessages
        );

        $payload = [
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user',   'content' => $userContent],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name'   => 'land_title_extraction',
                    'schema' => self::RESPONSE_SCHEMA,
                    'strict' => true,
                ],
            ],
            'max_tokens'  => 4000,
            'temperature' => 0,
        ];

        try {
            // OpenAI Vision can take 15–45s on complex docs; give it room.
            $response = Http::withToken($apiKey)
                ->timeout(75)
                ->connectTimeout(10)
                ->acceptJson()
                ->post(self::ENDPOINT, $payload);
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'AI service is unreachable right now. Try again in a moment.',
            ], 504);
        }

        if (!$response->ok()) {
            $detail = $response->json('error.message') ?: $response->body();
            Log::warning('OpenAI returned non-2xx', [
                'status' => $response->status(),
                'detail' => $detail,
            ]);
            return response()->json([
                'message' => 'AI scan failed: ' . substr((string) $detail, 0, 280),
            ], 502);
        }

        $raw = $response->json('choices.0.message.content');
        if (!$raw) {
            return response()->json([
                'message' => 'AI returned an empty response. Try a clearer image.',
            ], 502);
        }

        $parsed = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'AI returned malformed JSON. Try again with a clearer image.',
            ], 502);
        }

        // Light usage logging — useful when we later add per-user quotas
        Log::info('AI title scan completed', [
            'user_id'    => $user->id,
            'image_count'=> count($imageMessages),
            'confidence' => $parsed['confidence'] ?? null,
            'bearings'   => count($parsed['bearings'] ?? []),
        ]);

        return response()->json($parsed);
    }
}
