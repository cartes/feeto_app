<?php

declare(strict_types=1);

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class BlogContentSanitizer
{
    public function sanitize(?string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p[style],br,strong,b,em,i,u,s,strike,h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],ul,ol[start],li,blockquote,pre,code,hr,a[href|title|target|rel|class],img[src|alt|title|width|height|class],table[class|style],caption,colgroup,col[span|width],thead,tbody,tfoot,tr,th[colspan|rowspan|style],td[colspan|rowspan|style],span[style]');
        $config->set('CSS.AllowedProperties', ['text-align', 'width', 'min-width', 'color']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.AllowedComments', []);

        return (new HTMLPurifier($config))->purify($html ?? '');
    }
}
