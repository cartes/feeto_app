<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Genera el esquema estructurado (Schema.org JSON-LD) para la página
 * pública de agendamiento y presentación de un taller mecánico.
 */
final class TenantLandingSchema
{
    /**
     * Construye los esquemas estructurados para la landing del taller.
     *
     * @param  Collection<int, Branch>  $branches
     * @return array<int, array<string, mixed>>
     */
    public static function make(
        Tenant $tenant,
        Collection $branches,
        string $canonicalUrl,
        string $description,
        string $imageUrl
    ): array {
        return [
            self::buildBusinessSchema($tenant, $branches, $canonicalUrl, $description, $imageUrl),
            self::buildWebPageSchema($tenant, $canonicalUrl, $description, $imageUrl),
        ];
    }

    /**
     * Construye el esquema de tipo AutoRepair para el taller.
     *
     * @param  Collection<int, Branch>  $branches
     * @return array<string, mixed>
     */
    private static function buildBusinessSchema(
        Tenant $tenant,
        Collection $branches,
        string $canonicalUrl,
        string $description,
        string $imageUrl
    ): array {
        $businessSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoRepair',
            '@id' => "{$canonicalUrl}#business",
            'name' => $tenant->name,
            'url' => $canonicalUrl,
            'description' => $description,
            'image' => $imageUrl,
            'areaServed' => [
                '@type' => 'Country',
                'name' => $tenant->country()->label(),
            ],
        ];

        $address = self::buildAddress($tenant);
        if ($address !== null) {
            $businessSchema['address'] = $address;
        }

        $telephone = self::resolveTelephone($tenant);
        if ($telephone !== null) {
            $businessSchema['telephone'] = $telephone;
        }

        if (filled($tenant->website_url)) {
            $businessSchema['sameAs'] = [$tenant->website_url];
        }

        $locations = self::buildLocations($branches, $tenant, $canonicalUrl);
        if ($locations !== null) {
            $businessSchema['location'] = $locations;
        }

        $businessSchema['potentialAction'] = self::buildReserveAction($canonicalUrl);

        return $businessSchema;
    }

    /**
     * Construye la dirección postal del taller si existen datos de dirección o comuna.
     *
     * @return array<string, mixed>|null
     */
    private static function buildAddress(Tenant $tenant): ?array
    {
        if (blank($tenant->seo_address) && blank($tenant->comuna)) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $tenant->seo_address,
            'addressLocality' => $tenant->comuna,
            'addressCountry' => $tenant->country()->isoCode(),
        ], fn (mixed $value): bool => filled($value));
    }

    /**
     * Resuelve el número de teléfono principal del taller.
     */
    private static function resolveTelephone(Tenant $tenant): ?string
    {
        if (filled($tenant->phone)) {
            return $tenant->phone;
        }

        if (filled($tenant->whatsapp_number)) {
            return $tenant->whatsapp_number;
        }

        return null;
    }

    /**
     * Construye el bloque de ubicaciones de sucursales para el esquema del taller.
     *
     * @param  Collection<int, Branch>  $branches
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private static function buildLocations(Collection $branches, Tenant $tenant, string $canonicalUrl): ?array
    {
        if ($branches->isEmpty()) {
            return null;
        }

        $locations = $branches
            ->filter(fn (Branch $b): bool => filled($b->address) || filled($b->phone))
            ->map(fn (Branch $b): array => self::buildBranchLocation($b, $tenant, $canonicalUrl))
            ->values()
            ->all();

        if (empty($locations)) {
            return null;
        }

        return count($locations) === 1 ? $locations[0] : $locations;
    }

    /**
     * Construye el esquema de una sucursal individual.
     *
     * @return array<string, mixed>
     */
    private static function buildBranchLocation(Branch $branch, Tenant $tenant, string $canonicalUrl): array
    {
        $location = [
            '@type' => 'AutoRepair',
            'name' => $branch->name,
            'url' => $canonicalUrl,
        ];

        if (filled($branch->address)) {
            $location['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $branch->address,
                'addressCountry' => $tenant->country()->isoCode(),
            ];
        }

        if (filled($branch->phone)) {
            $location['telephone'] = $branch->phone;
        }

        return $location;
    }

    /**
     * Construye la acción potencial de reserva de cita en el taller.
     *
     * @return array<string, mixed>
     */
    private static function buildReserveAction(string $canonicalUrl): array
    {
        return [
            '@type' => 'ReserveAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $canonicalUrl,
                'inLanguage' => 'es-CL',
                'actionPlatform' => [
                    'https://schema.org/DesktopWebPlatform',
                    'https://schema.org/MobileWebPlatform',
                ],
            ],
            'result' => [
                '@type' => 'Reservation',
                'name' => 'Reserva de Cita en Taller',
            ],
        ];
    }

    /**
     * Construye el esquema de tipo WebPage para la landing del taller.
     *
     * @return array<string, mixed>
     */
    private static function buildWebPageSchema(
        Tenant $tenant,
        string $canonicalUrl,
        string $description,
        string $imageUrl
    ): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => "{$canonicalUrl}#webpage",
            'url' => $canonicalUrl,
            'name' => "Agendamiento - {$tenant->name}",
            'description' => $description,
            'about' => [
                '@id' => "{$canonicalUrl}#business",
            ],
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
            ],
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => config('app.name', 'TallerFlow'),
            ],
        ];
    }
}
