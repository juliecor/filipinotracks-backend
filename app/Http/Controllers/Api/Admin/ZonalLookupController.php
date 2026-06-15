<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Zonal-value lookup for the AI Title Scanner.
 *
 * Uses the same API the zonalvalue.ph website uses, so results match it exactly
 * and are already the COMPLETE DB + spreadsheet union:
 *   • GET {site}/api/regions?q=<province>            → resolves the province's domain (public)
 *   • GET {site}/api/zonal?domain=&barangay=&page=   → the merged, filtered rows (needs authToken cookie)
 *
 * The authToken (a Sanctum token from the zonal system) is stored server-side
 * in ZONAL_API_TOKEN and sent as a cookie, never exposed to the browser.
 *
 * Endpoint: GET /api/zonal-lookup   (sanctum + admin/staff)
 */
class ZonalLookupController extends Controller
{
    private const MAX_PAGES = 3;   // /api/zonal pages 16 rows each — enough to cover a barangay
    private const ROW_CAP   = 200;

    public function lookup(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'province' => 'required|string|max:120',
            'city'     => 'nullable|string|max:120',
            'barangay' => 'nullable|string|max:120',
        ]);

        $site  = rtrim((string) (config('services.zonal.site') ?: 'https://zonalvalue.ph'), '/');
        $token = config('services.zonal.token');
        if (!$token) {
            return response()->json([
                'configured' => false,
                'message' => 'Zonal valuation isn\'t connected yet — set ZONAL_API_TOKEN in the backend .env.',
            ], 503);
        }

        $province = trim($data['province']);
        $city     = trim($data['city'] ?? '');
        $barangay = trim($data['barangay'] ?? '');

        try {
            $domain = $this->resolveDomain($site, $province, $city);
        } catch (\Throwable $e) {
            Log::warning('Zonal regions lookup failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not reach the zonal service (regions). Check the backend internet / SSL.'], 504);
        }

        if (!$domain) {
            return response()->json([
                'configured' => true, 'covered' => false, 'matched_level' => 'none',
                'count' => 0, 'classifications' => [], 'rows' => [],
                'message' => "No zonal coverage for province \"{$province}\" yet.",
            ]);
        }

        try {
            $rows = $this->fetchZonal($site, $token, $domain, $barangay, $city);
        } catch (\Throwable $e) {
            Log::warning('Zonal fetch failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not reach the zonal service. Check the backend internet / SSL.'], 504);
        }

        if (empty($rows)) {
            $where = trim("{$barangay}, {$city}", ', ') ?: $province;
            return response()->json([
                'configured' => true, 'covered' => true, 'matched_level' => 'none',
                'count' => 0, 'classifications' => [], 'rows' => [],
                'message' => "No zonal entries for \"{$where}\". Check the barangay spelling on the review step.",
            ]);
        }

        $classifications = array_values(array_unique(array_filter(array_map(fn ($r) => $r['classification_code'], $rows))));
        sort($classifications);

        return response()->json([
            'configured'      => true,
            'covered'         => true,
            'matched_level'   => $barangay !== '' ? 'barangay' : ($city !== '' ? 'city' : 'province'),
            'count'           => count($rows),
            'classifications' => $classifications,
            'rows'            => $rows,
            'domain'          => $domain,
            'site'            => $site,
            'query'           => ['province' => $province, 'city' => $city, 'barangay' => $barangay],
        ]);
    }

    /* ─── helpers ─────────────────────────────────────────────────── */

    /** Public, read-only endpoints — skip SSL verify so local Windows cURL (no CA bundle) still works. */
    private function http()
    {
        return Http::withoutVerifying()->acceptJson();
    }

    /** Resolve a province (or city) → its zonalvalue.com domain via /api/regions (cached 24h). */
    private function resolveDomain(string $site, string $province, string $city): ?string
    {
        $key = 'zonal:domain:' . strtoupper($province);
        $cached = Cache::get($key);
        if ($cached) return $cached;

        foreach (array_filter([$province, $city]) as $q) {
            $resp = $this->http()->timeout(20)->get($site . '/api/regions', ['q' => $q]);
            if (!$resp->ok()) continue;

            $hit = null;
            foreach ($resp->json('matches') ?: [] as $m) {
                if (empty($m['domain'])) continue;
                if (strtoupper(trim($m['province'] ?? '')) === strtoupper($province)) { $hit = $m['domain']; break; }
                $hit ??= $m['domain'];
            }
            if ($hit) { Cache::put($key, $hit, now()->addHours(24)); return $hit; }
        }
        return null;
    }

    /**
     * Pull the merged, filtered rows from /api/zonal (DB + spreadsheet union),
     * paginating a few pages and caching per location for 12h.
     */
    private function fetchZonal(string $site, string $token, string $domain, string $barangay, string $city): array
    {
        $cacheKey = 'zonal:rows:' . md5($domain . '|' . strtoupper($barangay) . '|' . strtoupper($city));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) return $cached;

        $out = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $resp = $this->http()
                ->withHeaders(['Cookie' => "authToken={$token}"])
                ->timeout(30)
                ->get($site . '/api/zonal', array_filter([
                    'domain'   => $domain,
                    'page'     => $page,
                    'barangay' => $barangay,
                    'q'        => $barangay === '' ? $city : '',
                ], fn ($v) => $v !== null && $v !== ''));

            if (!$resp->ok()) break;
            $payload = $resp->json();
            foreach ($payload['rows'] ?? [] as $r) {
                $raw = $r['__zonal_raw'] ?? $r['ZonalValuepersqm.-'] ?? null;
                $out[] = [
                    'street_location'     => $r['Street/Subdivision-'] ?? null,
                    'vicinity'            => $r['Vicinity-'] ?? null,
                    'barangay'            => $r['Barangay-'] ?? null,
                    'city_municipality'   => $r['City-'] ?? null,
                    'province'            => $r['Province-'] ?? null,
                    'classification_code' => $r['Classification-'] ?? null,
                    'value_per_sqm'       => is_numeric($raw) ? (float) $raw : (float) preg_replace('/[^0-9.]/', '', (string) $raw),
                ];
            }
            if (empty($payload['hasNext']) || count($out) >= self::ROW_CAP) break;
        }

        if (!empty($out)) Cache::put($cacheKey, $out, now()->addHours(12));
        return $out;
    }
}
