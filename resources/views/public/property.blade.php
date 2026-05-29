<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $og['title'] }}</title>
    <meta name="description" content="{{ $og['description'] }}">

    {{-- Open Graph (Messenger / Viber / Facebook / Slack / Discord) --}}
    <meta property="og:title"       content="{{ $og['title'] }}">
    <meta property="og:description" content="{{ $og['description'] }}">
    <meta property="og:url"         content="{{ $og['url'] }}">
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="FilipinoTracks">
    <meta property="og:locale"      content="en_PH">

    @if ($og['image'])
        <meta property="og:image"            content="{{ $og['image'] }}">
        <meta property="og:image:secure_url" content="{{ $og['image'] }}">
        <meta property="og:image:width"      content="1280">
        <meta property="og:image:height"     content="670">
        <meta property="og:image:alt"        content="Satellite view of the property boundary">
        <meta name="twitter:card"            content="summary_large_image">
        <meta name="twitter:image"           content="{{ $og['image'] }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    <meta name="twitter:title"       content="{{ $og['title'] }}">
    <meta name="twitter:description" content="{{ $og['description'] }}">

    {{-- Redirect humans to the SPA. Crawlers (which don't run JS) stay on
         this page and read the OG tags above. --}}
    <meta http-equiv="refresh" content="0; url={{ $redirectUrl }}">
    <script>window.location.replace(@json($redirectUrl));</script>

    <style>
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #F4F6FB; color: #0A1628; }
        .wrap { max-width: 480px; margin: 96px auto; padding: 32px; text-align: center; }
        .badge { display:inline-block; width:54px; height:54px; line-height:54px; border-radius:12px; background: linear-gradient(135deg,#E6C76A,#C9A24A); color:#0A1628; font-weight:800; font-size:18px; }
        h1 { font-size: 1.15rem; margin: 18px 0 6px; }
        p { color: #64748B; font-size: 0.9rem; }
        a { color: #9F7E2C; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="badge">FT</div>
        <h1>Opening the property page…</h1>
        <p>If you are not redirected automatically, <a href="{{ $redirectUrl }}">click here to continue</a>.</p>
    </div>
</body>
</html>
