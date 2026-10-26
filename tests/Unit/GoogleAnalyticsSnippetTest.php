<?php

namespace Tests\Unit;

use App\Support\GoogleAnalyticsSnippet;
use PHPUnit\Framework\TestCase;

class GoogleAnalyticsSnippetTest extends TestCase
{
    private const STANDARD_SNIPPET = <<<'HTML'
<script async src="https://www.googletagmanager.com/gtag/js?id=G-ABC123"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-ABC123');
</script>
HTML;

    public function test_it_removes_the_blocking_loader_and_keeps_inline_code(): void
    {
        $output = GoogleAnalyticsSnippet::deferred(self::STANDARD_SNIPPET);

        $this->assertStringNotContainsString('<script async src=', $output);
        $this->assertStringContainsString("gtag('config', 'G-ABC123');", $output);
    }

    public function test_it_loads_gtag_after_window_load_with_a_delay(): void
    {
        $output = GoogleAnalyticsSnippet::deferred(self::STANDARD_SNIPPET);

        $this->assertStringContainsString("addEventListener('load'", $output);
        $this->assertStringContainsString('"https://www.googletagmanager.com/gtag/js?id=G-ABC123"', $output);
        $this->assertStringContainsString('},2000)', $output);
    }

    public function test_it_returns_unrecognised_code_untouched(): void
    {
        $code = '<script>console.log("gtm")</script>';

        $this->assertSame($code, GoogleAnalyticsSnippet::deferred($code));
    }
}
