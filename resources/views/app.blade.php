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

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        @if (($page['component'] ?? null) === 'Welcome')
            <link rel="preload" as="image" href="{{ asset('images/car-hero.webp') }}" fetchpriority="high">
        @endif

        <!-- Scripts -->
        @if (request()->routeIs('admin.*'))
            @routes('admin')
        @elseif (request()->routeIs('home') || request()->routeIs('pricing') || request()->routeIs('servicios') || request()->routeIs('orden-de-trabajo-taller-mecanico') || request()->routeIs('blog.*') || request()->routeIs('trial.*') || request()->routeIs('tracking.*') || request()->routeIs('talleres.*') || request()->routeIs('taller.landing') || request()->routeIs('taller.booking.store') || request()->routeIs('taller.whatsapp.inquiry') || request()->routeIs('checkout.*') || request()->routeIs('login') || request()->routeIs('logout') || request()->routeIs('register') || request()->routeIs('password.*') || request()->routeIs('verification.*'))
            @routes('public')
        @else
            @routes('tenant')
        @endif
        @php
            $realtimeComponents = ['Dashboard', 'Reception/Create', 'WorkOrders/Index'];
            $reverbEnabled = config('broadcasting.default') === 'reverb'
                && filled(config('broadcasting.connections.reverb.key'))
                && in_array($page['component'] ?? null, $realtimeComponents, true);
        @endphp
        <script>
            window.laravelReverbConfig = {
                enabled: @json($reverbEnabled),
                key: @json($reverbEnabled ? config('broadcasting.connections.reverb.key') : null),
                host: @json($reverbEnabled ? request()->getHost() : null),
                port: @json($reverbEnabled ? (request()->isSecure() ? 443 : 80) : null),
                scheme: @json($reverbEnabled ? (request()->isSecure() ? 'https' : 'http') : null)
            };
        </script>
        @vite('resources/js/app.js')
        @inertiaHead

        <!-- Google Search Console -->
        @if (filled($googleSearchConsoleCode = \App\Models\Setting::get('analytics_google_search_console_code')))
            {!! $googleSearchConsoleCode !!}
        @endif

        <!-- Google Analytics -->
        @if (filled($googleAnalyticsCode = \App\Models\Setting::get('analytics_google_analytics_code')))
            {!! $googleAnalyticsCode !!}
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
