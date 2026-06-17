<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves BIR zonal values via the zonalvalue.ph API (regions → domain →
 * merged DB+spreadsheet rows). Shared by the zonal-lookup endpoint and the
 * AI assistant's lookup tool.
 */
class ZonalService
{
    private const MAX_PAGES = 3;
    private const ROW_CAP   = 200;

    /**
     * @return array{configured:bool, covered?:bool, matched_level?:string, count?:int,
     *               classifications?:array, rows?:array, domain?:string, site?:string,
     *               query?:array, message?:string, http_status?:int}
     */
    public function lookup(string $province, string $city = '', string $barangay = ''): array
    {
        $province = trim($province);
        $city     = trim($city);
        $barangay = trim($barangay);

        $site  = rtrim((string) (config('services.zonal.site') ?: 'https://zonalvalue.ph'), '/');
        $token = config('services.zonal.token');
        if (!$token) {
            return ['configured' => false, 'http_status' => 503,
                'message' => 'Zonal valuation isn\'t connected yet — set ZONAL_API_TOKEN in the backend .env.'];
        }
        if ($province === '') {
            return ['configured' => true, 'covered' => false, 'matched_level' => 'none', 'count' => 0,
                'classifications' => [], 'rows' => [], 'message' => 'Province is required.'];
        }

        try {
            $domain = $this->resolveDomain($site, $province, $city);
        } catch (\Throwable $e) {
            Log::warning('Zonal regions lookup failed', ['error' => $e->getMessage()]);
            return ['configured' => true, 'covered' => false, 'count' => 0, 'rows' => [], 'classifications' => [],
                'http_status' => 504, 'message' => 'Could not reach the zonal service (regions). Check the backend internet / SSL.'];
        }

        if (!$domain) {
            return ['configured' => true, 'covered' => false, 'matched_level' => 'none', 'count' => 0,
                'classifications' => [], 'rows' => [], 'message' => "No zonal coverage for province \"{$province}\" yet."];
        }

        try {
            $rows = $this->fetchZonal($site, $token, $domain, $barangay, $city);
        } catch (\Throwable $e) {
            Log::warning('Zonal fetch failed', ['error' => $e->getMessage()]);
            return ['configured' => true, 'covered' => false, 'count' => 0, 'rows' => [], 'classifications' => [],
                'http_status' => 504, 'message' => 'Could not reach the zonal service. Check the backend internet / SSL.'];
        }

        if (empty($rows)) {
            $where = trim("{$barangay}, {$city}", ', ') ?: $province;
            return ['configured' => true, 'covered' => true, 'matched_level' => 'none', 'count' => 0,
                'classifications' => [], 'rows' => [],
                'message' => "No zonal entries for \"{$where}\". Check the barangay spelling."];
        }

        $classifications = array_values(array_unique(array_filter(array_map(fn ($r) => $r['classification_code'], $rows))));
        sort($classifications);

        return [
            'configured'      => true,
            'covered'         => true,
            'matched_level'   => $barangay !== '' ? 'barangay' : ($city !== '' ? 'city' : 'province'),
            'count'           => count($rows),
            'classifications' => $classifications,
            'rows'            => $rows,
            'domain'          => $domain,
            'site'            => $site,
            'query'           => ['province' => $province, 'city' => $city, 'barangay' => $barangay],
        ];
    }

    /** Per-classification median ₱/sqm — compact summary for the AI tool result. */
    public function summarize(array $lookup): array
    {
        if (empty($lookup['rows'])) {
            return ['found' => false, 'message' => $lookup['message'] ?? 'No data.'];
        }
        $byClass = [];
        foreach ($lookup['rows'] as $r) {
            $c = $r['classification_code'] ?: '—';
            $byClass[$c][] = (float) $r['value_per_sqm'];
        }
        $medians = [];
        foreach ($byClass as $c => $vals) {
            sort($vals);
            $n = count($vals);
            $medians[$c] = round($n % 2 ? $vals[intdiv($n, 2)] : ($vals[$n / 2 - 1] + $vals[$n / 2]) / 2, 2);
        }
        $zones = array_values(array_unique(array_filter(array_map(fn ($r) => $r['city_municipality'], $lookup['rows']))));
        return [
            'found'              => true,
            'location'           => $lookup['query'] ?? null,
            'matched_bir_zone'   => implode(', ', $zones),
            'entries'            => $lookup['count'] ?? 0,
            'zonal_value_per_sqm_by_classification' => $medians,
        ];
    }

    /* ─── internals ───────────────────────────────────────────────── */

    private function http()
    {
        return Http::withoutVerifying()->acceptJson();
    }

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
