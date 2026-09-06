<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $seo = $page['props']['seo'] ?? null;
        $seoTitle = is_array($seo) && filled($seo['title'] ?? null)
            ? $seo['title']
            : config('app.name', 'Laravel');
        $seoDescription = is_array($seo) ? ($seo['description'] ?? null) : null;
        $seoImage = is_array($seo) ? ($seo['og_image'] ?? null) : null;
        $seoImageAlt = is_array($seo) ? ($seo['og_image_alt'] ?? $seoTitle) : null;
        $seoImageWidth = is_array($seo) ? ($seo['og_image_width'] ?? null) : null;
        $seoImageHeight = is_array($seo) ? ($seo['og_image_height'] ?? null) : null;
        $seoOgType = is_array($seo) && filled($seo['og_type'] ?? null) ? $seo['og_type'] : 'website';
        $seoTwitterCard = is_array($seo) && filled($seo['twitter_card'] ?? null) ? $seo['twitter_card'] : 'summary_large_image';
        $seoPublishedTime = is_array($seo) ? ($seo['published_time'] ?? null) : null;
        $seoModifiedTime = is_array($seo) ? ($seo['modified_time'] ?? null) : null;
        $canonicalUrl = is_array($seo) && filled($seo['canonical_url'] ?? null)
            ? $seo['canonical_url']
            : request()->url();
        // Páginas sin bloque `seo` (app del taller, admin, login, tracking, checkout) no se indexan.
        // Nota: esto depende de que exista el bloque `seo` (identidad de página pública),
        // no de si el admin dejó la descripción vacía — una descripción vacía no debe
        // des-indexar la página ni quitarle canonical/OG.
        $seoRobots = is_array($seo)
            ? ($seo['robots'] ?? \App\Support\MarketingSeoPages::DEFAULT_ROBOTS)
            : \App\Support\MarketingSeoPages::NOINDEX_ROBOTS;
        $seoSchema = is_array($seo) ? ($seo['schema'] ?? null) : null;
        $seoSchemaItems = is_array($seoSchema)
            ? (array_is_list($seoSchema) ? $seoSchema : [$seoSchema])
            : [];
    @endphp
    <head>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Los tags marcados con `inertia` son reemplazados por <SeoHead> en el cliente (sin duplicados). --}}
        <title inertia>{{ $seoTitle }}</title>
        @if (is_array($seo))
            <meta name="robots" content="{{ $seoRobots }}" inertia>
            @if (filled($seoDescription))
                <meta name="description" content="{{ $seoDescription }}" inertia>
            @endif
            <link rel="canonical" href="{{ $canonicalUrl }}" inertia>
            <meta property="og:type" content="{{ $seoOgType }}" inertia>
            <meta property="og:site_name" content="{{ \App\Support\MarketingSeoPages::SITE_NAME }}" inertia>
            <meta property="og:locale" content="{{ \App\Support\MarketingSeoPages::LOCALE }}" inertia>
            <meta property="og:title" content="{{ $seoTitle }}" inertia>
            @if (filled($seoDescription))
                <meta property="og:description" content="{{ $seoDescription }}" inertia>
            @endif
            <meta property="og:url" content="{{ $canonicalUrl }}" inertia>
            @if (filled($seoImage))
                <meta property="og:image" content="{{ $seoImage }}" inertia>
                <meta property="og:image:alt" content="{{ $seoImageAlt }}" inertia>
                @if (filled($seoImageWidth) && filled($seoImageHeight))
                    <meta property="og:image:width" content="{{ $seoImageWidth }}" inertia>
                    <meta property="og:image:height" content="{{ $seoImageHeight }}" inertia>
                @endif
            @endif
            @if ($seoOgType === 'article')
                @if (filled($seoPublishedTime))
                    <meta property="article:published_time" content="{{ $seoPublishedTime }}" inertia>
                @endif
                @if (filled($seoModifiedTime))
                    <meta property="article:modified_time" content="{{ $seoModifiedTime }}" inertia>
                @endif
            @endif
            <meta name="twitter:card" content="{{ $seoTwitterCard }}" inertia>
            <meta name="twitter:site" content="{{ \App\Support\MarketingSeoPages::TWITTER_SITE }}" inertia>
            <meta name="twitter:title" content="{{ $seoTitle }}" inertia>
            @if (filled($seoDescription))
                <meta name="twitter:description" content="{{ $seoDescription }}" inertia>
            @endif
            @if (filled($seoImage))
                <meta name="twitter:image" content="{{ $seoImage }}" inertia>
                <meta name="twitter:image:alt" content="{{ $seoImageAlt }}" inertia>
            @endif
        @else
            <meta name="robots" content="{{ $seoRobots }}">
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
