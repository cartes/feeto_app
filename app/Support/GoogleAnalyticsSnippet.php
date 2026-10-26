<?php

namespace App\Support;

class GoogleAnalyticsSnippet
{
    private const LOADER_PATTERN = '#<script\b[^>]*\bsrc=["\']https://www\.googletagmanager\.com/gtag/js\?id=([A-Za-z0-9_-]+)["\'][^>]*>\s*</script>#i';

    private const FALLBACK_DELAY_MS = 10000;

    /**
     * Pospone la descarga de gtag.js hasta la primera interacción (scroll, pointerdown
     * o keydown), con un respaldo temporal para no perder las visitas que rebotan sin
     * interactuar. Conserva el código inline pegado por el admin. Si no es un snippet gtag.js, lo devuelve intacto.
     */
    public static function deferred(string $code): string
    {
        if (! preg_match(self::LOADER_PATTERN, $code, $matches)) {
            return $code;
        }

        $inlineCode = preg_replace(self::LOADER_PATTERN, '', $code);
        $loaderUrl = 'https://www.googletagmanager.com/gtag/js?id='.$matches[1];
        $loaderUrlJson = json_encode($loaderUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $delay = self::FALLBACK_DELAY_MS;

        return $inlineCode."\n<script>(function(){var loaded=false,events=['scroll','pointerdown','keydown'];function load(){if(loaded){return;}loaded=true;events.forEach(function(e){removeEventListener(e,load);});var s=document.createElement('script');s.src={$loaderUrlJson};s.async=true;document.head.appendChild(s);}events.forEach(function(e){addEventListener(e,load,{once:true,passive:true});});addEventListener('load',function(){setTimeout(load,{$delay});});})();</script>";
    }
}
