<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server-side wrapper for /p/{code} that injects Open Graph + Twitter Card
 * meta tags so when the share link is pasted in Messenger, Viber, FB, Slack,
 * etc. it renders a rich preview (with the boundary's static map as the
 * thumbnail).
 *
 * Production assumption: Laravel serves the built SPA from public/index.html.
 * In that case this controller reads index.html, injects the meta tags into
 * <head>, and returns it — so the React app still boots normally for humans
 * and crawlers see the metadata they need.
 *
 * Development fallback: when public/index.html does not exist (the SPA runs
 * separately on Vite at port 5173), the controller renders a tiny HTML stub
 * with the same OG tags + a JS/meta redirect to the frontend dev URL.
 */
class PublicPropertyPageController extends Controller
{
    private const SHAREABLE_STATUSES = ['approved', 'released'];

    public function show(Request $request, string $code): Response
    {
        $transaction = Transaction::with('propertyMap')
            ->where('transaction_code', $code)
            ->first();

        $isShareable = $transaction
            && in_array($transaction->status, self::SHAREABLE_STATUSES, true)
            && $transaction->propertyMap;

        $og = $this->buildOgData($request, $code, $transaction, $isShareable);

        // PRODUCTION: SPA's index.html is sitting in public/. Inject and serve.
        $indexPath = public_path('index.html');
        if (file_exists($indexPath)) {
            $html = file_get_contents($indexPath);
            $tags = $this->renderOgTags($og);

            // Inject right after <head>, falling back to prepending if not found.
            if (stripos($html, '<head') !== false) {
                $html = preg_replace('/(<head[^>]*>)/i', '$1' . PHP_EOL . $tags, $html, 1);
            } else {
                $html = $tags . $html;
            }

            return response($html, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                // Crawlers cache OG, so a short window is fine; humans get the
                // SPA which will re-fetch live data anyway.
                ->header('Cache-Control', 'public, max-age=300');
        }

        // DEVELOPMENT: serve a minimal page with OG tags + redirect to Vite.
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        return response()->view('public.property', [
            'og'          => $og,
            'redirectUrl' => "{$frontendUrl}/p/{$code}",
        ]);
    }

    private function buildOgData(Request $request, string $code, ?Transaction $transaction, bool $isShareable): array
    {
        $url = $request->fullUrl();

        if (!$isShareable || !$transaction) {
            return [
                'title'       => 'Property not available · FilipinoTracks',
                'description' => 'This property link is no longer available. Visit FilipinoTracks to browse other verified properties.',
                'image'       => null,
                'url'         => $url,
            ];
        }

        $map      = $transaction->propertyMap;
        $owner    = $map->registered_owner ?: 'Verified Property';
        $location = collect([$map->city_municipality, $map->province])->filter()->implode(', ');
        $title    = $owner . ' · FilipinoTracks';

        $description = $location
            ? "Verified property in {$location}. View boundary, area, and details on FilipinoTracks."
            : 'Verified property record on FilipinoTracks. View boundary, area, and details.';

        // 1.91:1 aspect ratio (FB recommended) — 640×335 with scale=2 → 1280×670
        $image = $map->staticMapUrl(640, 335);

        return [
            'title'       => $title,
            'description' => $description,
            'image'       => $image,
            'url'         => $url,
        ];
    }

    private function renderOgTags(array $og): string
    {
        $title = e($og['title']);
        $desc  = e($og['description']);
        $url   = e($og['url']);

        $tags = [
            "<title>{$title}</title>",
            "<meta name=\"description\" content=\"{$desc}\">",
            // Open Graph
            "<meta property=\"og:title\" content=\"{$title}\">",
            "<meta property=\"og:description\" content=\"{$desc}\">",
            "<meta property=\"og:url\" content=\"{$url}\">",
            "<meta property=\"og:type\" content=\"website\">",
            "<meta property=\"og:site_name\" content=\"FilipinoTracks\">",
            "<meta property=\"og:locale\" content=\"en_PH\">",
            // Twitter
            "<meta name=\"twitter:title\" content=\"{$title}\">",
            "<meta name=\"twitter:description\" content=\"{$desc}\">",
        ];

        if (!empty($og['image'])) {
            $image = e($og['image']);
            $tags[] = "<meta property=\"og:image\" content=\"{$image}\">";
            $tags[] = "<meta property=\"og:image:secure_url\" content=\"{$image}\">";
            $tags[] = "<meta property=\"og:image:width\" content=\"1280\">";
            $tags[] = "<meta property=\"og:image:height\" content=\"670\">";
            $tags[] = "<meta property=\"og:image:alt\" content=\"Satellite view of the property boundary\">";
            $tags[] = "<meta name=\"twitter:card\" content=\"summary_large_image\">";
            $tags[] = "<meta name=\"twitter:image\" content=\"{$image}\">";
        } else {
            $tags[] = "<meta name=\"twitter:card\" content=\"summary\">";
        }

        return '    ' . implode(PHP_EOL . '    ', $tags) . PHP_EOL;
    }
}
