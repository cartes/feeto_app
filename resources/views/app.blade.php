<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $seo = $page['props']['seo'] ?? null;
        $seoTitle = is_array($seo) && filled($seo['title'] ?? null)
            ? $seo['title']
            : config('app.name', 'Laravel');
        $seoDescription = is_array($seo) ? ($seo['description'] ?? null) : null;
        $seoImage = is_array($seo) ? ($seo['og_image'] ?? null) : null;
        $canonicalUrl = is_array($seo) && filled($seo['canonical_url'] ?? null)
            ? $seo['canonical_url']
            : request()->fullUrl();
        $seoSchema = is_array($seo) ? ($seo['schema'] ?? null) : null;
        $seoSchemaItems = is_array($seoSchema)
            ? (array_is_list($seoSchema) ? $seoSchema : [$seoSchema])
            : [];
    @endphp
    <head>
        <!-- Google Search Console -->
        @if (filled($googleSearchConsoleCode = \App\Models\Setting::get('analytics_google_search_console_code')))
            {!! $googleSearchConsoleCode !!}
        @endif

        <!-- Google Analytics -->
        @if (filled($googleAnalyticsCode = \App\Models\Setting::get('analytics_google_analytics_code')))
            {!! $googleAnalyticsCode !!}
        @endif

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $seoTitle }}</title>
        @if (filled($seoDescription))
            <meta name="description" content="{{ $seoDescription }}">
            <meta name="robots" content="index, follow">
            <link rel="canonical" href="{{ $canonicalUrl }}">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $seoTitle }}">
            <meta property="og:description" content="{{ $seoDescription }}">
            <meta property="og:url" content="{{ $canonicalUrl }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seoTitle }}">
            <meta name="twitter:description" content="{{ $seoDescription }}">
            @if (filled($seoImage))
                <meta property="og:image" content="{{ $seoImage }}">
                <meta name="twitter:image" content="{{ $seoImage }}">
            @endif
        @endif
        @foreach ($seoSchemaItems as $schemaItem)
            @if (is_array($schemaItem) && $schemaItem !== [])
                <script type="application/ld+json">{!! json_encode($schemaItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
            @endif
        @endforeach

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Scripts -->
        @routes
        <script>
            window.laravelReverbConfig = {
                key: "{{ config('broadcasting.connections.reverb.key') }}",
                host: "{{ config('broadcasting.connections.reverb.options.host') }}",
                port: "{{ config('broadcasting.connections.reverb.options.port') }}",
                scheme: "{{ config('broadcasting.connections.reverb.options.scheme') }}"
            };
        </script>
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
