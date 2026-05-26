<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;

abstract class Controller
{
    /**
     * @return array{title: string, description: string, og_image: string, canonical_url: string, schema: array<int, array<string, mixed>>}
     */
    protected function resolveMarketingSeo(string $pageKey, string $defaultTitle, string $defaultDescription): array
    {
        $title = Setting::get("seo_{$pageKey}_title", $defaultTitle);
        $description = Setting::get("seo_{$pageKey}_description", $defaultDescription);
        $canonicalUrl = request()->url();
        $ogImage = $this->resolveSocialImageUrl(Setting::get("seo_{$pageKey}_og_image", ''));

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonicalUrl,
            'og_image' => $ogImage,
            'schema' => $this->resolveMarketingSchema($pageKey, $title, $description, $canonicalUrl, $ogImage),
        ];
    }

    protected function resolveSocialImageUrl(?string $configuredUrl = null): string
    {
        if (filled($configuredUrl)) {
            return $configuredUrl;
        }

        return url('/images/tallerflow-social-share.png');
    }

    /**
     * Genera esquemas estructurados para las páginas de marketing (Home, Precios, Trial)
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
            'name' => config('app.name', 'TallerFlow'),
            'description' => 'Software de gestión para talleres mecánicos en Chile',
        ];

        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('app.name', 'TallerFlow'),
            'url' => url('/'),
            'logo' => url('/images/tallerflow-logo.png'),
        ];

        if ($pageKey === 'home') {
            $softwareSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                '@id' => url('/').'#software',
                'name' => config('app.name', 'TallerFlow'),
                'description' => $description,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'All',
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

            return [
                $websiteSchema,
                $organizationSchema,
                $softwareSchema,
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    '@id' => "{$canonicalUrl}#webpage",
                    'url' => $canonicalUrl,
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        '@id' => url('/').'#website',
                    ],
                    'about' => [
                        '@id' => url('/').'#software',
                    ],
                ],
            ];
        }

        if ($pageKey === 'pricing') {
            $productSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                '@id' => "{$canonicalUrl}#product",
                'name' => 'Suscripción TallerFlow',
                'description' => 'Planes de suscripción mensual y anual para el software de talleres mecánicos TallerFlow.',
                'image' => $ogImage,
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'CLP',
                    'lowPrice' => '19990',
                    'highPrice' => '49990',
                    'offerCount' => 3,
                    'offers' => [
                        [
                            '@type' => 'Offer',
                            'name' => 'Plan Básico',
                            'price' => '19990',
                            'priceCurrency' => 'CLP',
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'Plan Pro',
                            'price' => '29990',
                            'priceCurrency' => 'CLP',
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'Plan Premium',
                            'price' => '49990',
                            'priceCurrency' => 'CLP',
                        ],
                    ],
                ],
            ];

            return [
                $websiteSchema,
                $productSchema,
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    '@id' => "{$canonicalUrl}#webpage",
                    'url' => $canonicalUrl,
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        '@id' => url('/').'#website',
                    ],
                ],
            ];
        }

        return [
            $websiteSchema,
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                '@id' => "{$canonicalUrl}#webpage",
                'url' => $canonicalUrl,
                'name' => $title,
                'description' => $description,
                'isPartOf' => [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                ],
            ],
        ];
    }
}
