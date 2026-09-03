<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\MarketingSeoPages;

abstract class Controller
{
    /**
     * Resuelve el bloque `seo` de una página pública de marketing. Los valores
     * editados desde el panel de super-admin (Landing SEO) tienen prioridad
     * sobre los defaults definidos en MarketingSeoPages.
     *
     * @return array<string, mixed>
     */
    protected function resolveMarketingSeo(string $pageKey, ?string $defaultTitle = null, ?string $defaultDescription = null): array
    {
        $page = MarketingSeoPages::has($pageKey) ? MarketingSeoPages::get($pageKey) : null;

        $title = Setting::get("seo_{$pageKey}_title", $defaultTitle ?? $page['default_title'] ?? config('app.name', 'TallerFlow'));
        $description = Setting::get("seo_{$pageKey}_description", $defaultDescription ?? $page['default_description'] ?? '');
        $canonicalUrl = request()->url();
        $image = $this->resolveSocialImage(Setting::get("seo_{$pageKey}_og_image", ''));

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonicalUrl,
            'robots' => MarketingSeoPages::DEFAULT_ROBOTS,
            'og_type' => $page['og_type'] ?? 'website',
            'og_image' => $image['url'],
            'og_image_alt' => $title,
            'og_image_width' => $image['width'],
            'og_image_height' => $image['height'],
            'twitter_card' => 'summary_large_image',
            'schema' => $this->resolveMarketingSchema($pageKey, $title, $description, $canonicalUrl, $image['url']),
        ];
    }

    protected function resolveSocialImageUrl(?string $configuredUrl = null): string
    {
        return $this->resolveSocialImage($configuredUrl)['url'];
    }

    /**
     * Devuelve la imagen social a usar. Solo se informan dimensiones cuando se
     * trata de la imagen por defecto (de la que conocemos el tamaño real).
     *
     * @return array{url: string, width: int|null, height: int|null}
     */
    protected function resolveSocialImage(?string $configuredUrl = null): array
    {
        if (filled($configuredUrl)) {
            return ['url' => $configuredUrl, 'width' => null, 'height' => null];
        }

        return [
            'url' => url(MarketingSeoPages::DEFAULT_SOCIAL_IMAGE),
            'width' => MarketingSeoPages::DEFAULT_SOCIAL_IMAGE_WIDTH,
            'height' => MarketingSeoPages::DEFAULT_SOCIAL_IMAGE_HEIGHT,
        ];
    }

    /** @return array<string, mixed> */
    protected function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => MarketingSeoPages::SITE_NAME,
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url(MarketingSeoPages::LOGO_IMAGE),
            ],
        ];
    }

    /**
     * @param  list<array{name: string, item: string}>  $items
     * @return array<string, mixed>
     */
    protected function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['item'],
            ])->all(),
        ];
    }

    /**
     * Genera esquemas estructurados para las páginas de marketing.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function resolveMarketingSchema(string $pageKey, string $title, string $description, string $canonicalUrl, string $ogImage): array
    {
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => MarketingSeoPages::SITE_NAME,
            'description' => 'Software de gestión para talleres mecánicos en Chile',
            'inLanguage' => 'es-CL',
        ];

        $organizationSchema = $this->organizationSchema();

        $webPageSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => "{$canonicalUrl}#webpage",
            'url' => $canonicalUrl,
            'name' => $title,
            'description' => $description,
            'inLanguage' => 'es-CL',
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
            ],
        ];

        if ($pageKey === 'home') {
            $softwareSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                '@id' => url('/').'#software',
                'name' => MarketingSeoPages::SITE_NAME,
                'description' => $description,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'All',
                'image' => $ogImage,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'CLP',
                    'priceSpecification' => [
                        '@type' => 'PriceSpecification',
                        'price' => '0',
                        'priceCurrency' => 'CLP',
                        'name' => 'Prueba gratuita de 14 días',
                    ],
                ],
            ];

            $webPageSchema['about'] = ['@id' => url('/').'#software'];

            return [$websiteSchema, $organizationSchema, $softwareSchema, $webPageSchema];
        }

        if ($pageKey === 'pricing') {
            $productSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                '@id' => "{$canonicalUrl}#product",
                'name' => 'Suscripción TallerFlow',
                'description' => 'Planes de suscripción mensual y anual para el software de talleres mecánicos TallerFlow.',
                'image' => $ogImage,
                'brand' => ['@id' => url('/').'#organization'],
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'CLP',
                    'lowPrice' => '19990',
                    'highPrice' => '49990',
                    'offerCount' => 3,
                    'offers' => [
                        ['@type' => 'Offer', 'name' => 'Plan Básico', 'price' => '19990', 'priceCurrency' => 'CLP'],
                        ['@type' => 'Offer', 'name' => 'Plan Pro', 'price' => '29990', 'priceCurrency' => 'CLP'],
                        ['@type' => 'Offer', 'name' => 'Plan Premium', 'price' => '49990', 'priceCurrency' => 'CLP'],
                    ],
                ],
            ];

            return [$websiteSchema, $organizationSchema, $productSchema, $webPageSchema];
        }

        if ($pageKey === 'orden_trabajo') {
            $articleSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                '@id' => "{$canonicalUrl}#article",
                'headline' => $title,
                'description' => $description,
                'image' => $ogImage,
                'url' => $canonicalUrl,
                'inLanguage' => 'es-CL',
                'author' => ['@id' => url('/').'#organization'],
                'publisher' => ['@id' => url('/').'#organization'],
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
            ];

            $breadcrumbs = $this->breadcrumbSchema([
                ['name' => 'Inicio', 'item' => url('/')],
                ['name' => 'Orden de Trabajo', 'item' => $canonicalUrl],
            ]);

            return [$websiteSchema, $organizationSchema, $articleSchema, $breadcrumbs];
        }

        $breadcrumbs = $this->breadcrumbSchema([
            ['name' => 'Inicio', 'item' => url('/')],
            ['name' => $title, 'item' => $canonicalUrl],
        ]);

        return [$websiteSchema, $organizationSchema, $webPageSchema, $breadcrumbs];
    }
}
