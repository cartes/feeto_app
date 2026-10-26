<?php

namespace App\Support;

class GoogleAnalyticsSnippet
{
    private const LOADER_PATTERN = '#<script\b[^>]*\bsrc=["\']https://www\.googletagmanager\.com/gtag/js\?id=([A-Za-z0-9_-]+)["\'][^>]*>\s*</script>#i';

    private const LOAD_DELAY_MS = 2000;

    /**
     * Pospone la descarga de gtag.js hasta después del evento `load`, conservando el
     * código inline pegado por el admin. Si no es un snippet gtag.js, lo devuelve intacto.
     */
    public static function deferred(string $code): string
    {
        if (! preg_match(self::LOADER_PATTERN, $code, $matches)) {
            return $code;
        }

        $inlineCode = preg_replace(self::LOADER_PATTERN, '', $code);
        $loaderUrl = 'https://www.googletagmanager.com/gtag/js?id='.$matches[1];
        $loaderUrlJson = json_encode($loaderUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $delay = self::LOAD_DELAY_MS;

        return $inlineCode."\n<script>window.addEventListener('load',function(){setTimeout(function(){var s=document.createElement('script');s.src={$loaderUrlJson};s.async=true;document.head.appendChild(s);},{$delay});});</script>";
    }
}
