<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicTenantDirectoryController extends Controller
{
    private const LATEST_COUNT = 6;

    public function index(Request $request): Response
    {
        $comuna = $request->query('comuna');
        $canonicalUrl = route('talleres.index');

        $comunas = $this->eligibleTenantsQuery()
            ->select('comuna')
            ->distinct()
            ->orderBy('comuna')
            ->pluck('comuna');

        $latestTenants = $this->eligibleTenantsQuery()
            ->orderByDesc('created_at')
            ->take(self::LATEST_COUNT)
            ->get(['id', 'name', 'slug', 'comuna', 'seo_address', 'website_url', 'created_at']);

        $tenants = $this->eligibleTenantsQuery()
            ->when(filled($comuna), fn (Builder $query) => $query->where('comuna', $comuna))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'comuna', 'seo_address', 'website_url', 'created_at']);

        $title = 'Directorio de Talleres Mecánicos en Chile · TallerFlow';
        $description = 'Encuentra talleres mecánicos confiables en tu comuna. Agenda tu hora directamente con cada taller asociado a TallerFlow.';

        return Inertia::render('Public/TenantDirectory', [
            'latestTenants' => $this->mapTenants($latestTenants),
            'tenants' => $this->mapTenants($tenants),
            'comunas' => $comunas,
            'filters' => [
                'comuna' => $comuna,
            ],
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical_url' => $canonicalUrl,
                'og_image' => $this->resolveSocialImageUrl(),
                'og_image_alt' => 'Directorio de Talleres TallerFlow',
                'og_image_width' => 1200,
                'og_image_height' => 630,
                'twitter_card' => 'summary_large_image',
                'schema' => $this->resolveDirectorySchema($tenants, $canonicalUrl, $title, $description),
            ],
        ]);
    }

    private function eligibleTenantsQuery(): Builder
    {
        return Tenant::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNotNull('comuna')
            ->where('comuna', '!=', '');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapTenants(Collection $tenants): array
    {
        return $tenants->map(fn (Tenant $tenant): array => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'comuna' => $tenant->comuna,
            'address' => $tenant->seo_address,
            'website_url' => $tenant->website_url,
            'landing_url' => route('taller.landing', ['tenantBySlug' => $tenant->slug]),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Tenant>  $tenants
     * @return array<int, array<string, mixed>>
     */
    private function resolveDirectorySchema(Collection $tenants, string $canonicalUrl, string $title, string $description): array
    {
        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            '@id' => "{$canonicalUrl}#directory",
            'name' => 'Directorio de Talleres Mecánicos TallerFlow',
            'itemListElement' => $tenants->values()->map(function (Tenant $tenant, int $index): array {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('taller.landing', ['tenantBySlug' => $tenant->slug]),
                    'name' => $tenant->name,
                ];
            })->all(),
        ];

        return [
            $itemList,
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                '@id' => "{$canonicalUrl}#webpage",
                'url' => $canonicalUrl,
                'name' => $title,
                'description' => $description,
                'inLanguage' => 'es-CL',
                'isPartOf' => [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Talleres', 'item' => $canonicalUrl],
                ],
            ],
        ];
    }
}
