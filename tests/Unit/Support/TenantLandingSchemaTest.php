<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Enums\Country;
use App\Models\Branch;
use App\Models\Tenant;
use App\Support\TenantLandingSchema;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class TenantLandingSchemaTest extends TestCase
{
    public function test_it_builds_minimal_schema_without_address_or_branches(): void
    {
        $tenant = new Tenant([
            'name' => 'Taller Los Andes',
            'country_code' => Country::Chile->value,
        ]);

        $canonicalUrl = 'https://tallerflow.cl/taller/los-andes';
        $description = 'Taller mecánico especialista en frenos';
        $imageUrl = 'https://tallerflow.cl/images/og.png';

        $schema = TenantLandingSchema::make(
            $tenant,
            new Collection,
            $canonicalUrl,
            $description,
            $imageUrl
        );

        $this->assertCount(2, $schema);

        $business = $schema[0];
        $this->assertSame('https://schema.org', $business['@context']);
        $this->assertSame('AutoRepair', $business['@type']);
        $this->assertSame("{$canonicalUrl}#business", $business['@id']);
        $this->assertSame('Taller Los Andes', $business['name']);
        $this->assertSame($canonicalUrl, $business['url']);
        $this->assertSame($description, $business['description']);
        $this->assertSame($imageUrl, $business['image']);
        $this->assertSame('Chile', $business['areaServed']['name']);
        $this->assertArrayNotHasKey('address', $business);
        $this->assertArrayNotHasKey('telephone', $business);
        $this->assertArrayNotHasKey('sameAs', $business);
        $this->assertArrayNotHasKey('location', $business);
        $this->assertSame('ReserveAction', $business['potentialAction']['@type']);

        $webpage = $schema[1];
        $this->assertSame('https://schema.org', $webpage['@context']);
        $this->assertSame('WebPage', $webpage['@type']);
        $this->assertSame("{$canonicalUrl}#webpage", $webpage['@id']);
        $this->assertSame("Agendamiento - {$tenant->name}", $webpage['name']);
        $this->assertSame($imageUrl, $webpage['primaryImageOfPage']['url']);
        $this->assertSame("{$canonicalUrl}#business", $webpage['about']['@id']);
    }

    public function test_it_includes_address_when_seo_address_or_comuna_are_present(): void
    {
        $tenant = new Tenant([
            'name' => 'Taller Providencia',
            'country_code' => Country::Chile->value,
            'seo_address' => 'Av. Providencia 1234',
            'comuna' => 'Providencia',
        ]);

        $schema = TenantLandingSchema::make(
            $tenant,
            new Collection,
            'https://tallerflow.cl/taller/providencia',
            'Descripción',
            'https://tallerflow.cl/images/og.png'
        );

        $business = $schema[0];
        $this->assertArrayHasKey('address', $business);
        $this->assertSame('PostalAddress', $business['address']['@type']);
        $this->assertSame('Av. Providencia 1234', $business['address']['streetAddress']);
        $this->assertSame('Providencia', $business['address']['addressLocality']);
        $this->assertSame('CL', $business['address']['addressCountry']);
    }

    public function test_it_resolves_telephone_preferring_phone_over_whatsapp(): void
    {
        $tenantWithPhone = new Tenant([
            'name' => 'Taller Uno',
            'country_code' => Country::Chile->value,
            'phone' => '+56911111111',
            'whatsapp_number' => '+56922222222',
        ]);

        $schema1 = TenantLandingSchema::make(
            $tenantWithPhone,
            new Collection,
            'https://example.com/taller-uno',
            'Desc',
            'https://example.com/og.png'
        );

        $this->assertSame('+56911111111', $schema1[0]['telephone']);

        $tenantWithWhatsappOnly = new Tenant([
            'name' => 'Taller Dos',
            'country_code' => Country::Chile->value,
            'whatsapp_number' => '+56922222222',
        ]);

        $schema2 = TenantLandingSchema::make(
            $tenantWithWhatsappOnly,
            new Collection,
            'https://example.com/taller-dos',
            'Desc',
            'https://example.com/og.png'
        );

        $this->assertSame('+56922222222', $schema2[0]['telephone']);
    }

    public function test_it_includes_same_as_when_website_url_is_present(): void
    {
        $tenant = new Tenant([
            'name' => 'Taller Web',
            'country_code' => Country::Chile->value,
            'website_url' => 'https://mitaller.cl',
        ]);

        $schema = TenantLandingSchema::make(
            $tenant,
            new Collection,
            'https://example.com/taller-web',
            'Desc',
            'https://example.com/og.png'
        );

        $this->assertSame(['https://mitaller.cl'], $schema[0]['sameAs']);
    }

    public function test_it_builds_single_branch_location_as_object(): void
    {
        $tenant = new Tenant([
            'name' => 'Taller Central',
            'country_code' => Country::Chile->value,
        ]);

        $branch = new Branch([
            'name' => 'Casa Matriz',
            'address' => 'Alameda 100',
            'phone' => '+56933333333',
        ]);

        $schema = TenantLandingSchema::make(
            $tenant,
            new Collection([$branch]),
            'https://example.com/taller-central',
            'Desc',
            'https://example.com/og.png'
        );

        $location = $schema[0]['location'];
        $this->assertIsArray($location);
        $this->assertSame('AutoRepair', $location['@type']);
        $this->assertSame('Casa Matriz', $location['name']);
        $this->assertSame('https://example.com/taller-central', $location['url']);
        $this->assertSame('Alameda 100', $location['address']['streetAddress']);
        $this->assertSame('CL', $location['address']['addressCountry']);
        $this->assertSame('+56933333333', $location['telephone']);
    }

    public function test_it_builds_multiple_branches_as_array_and_filters_empty_branches(): void
    {
        $tenant = new Tenant([
            'name' => 'Cadena Talleres',
            'country_code' => Country::Chile->value,
        ]);

        $branch1 = new Branch(['name' => 'Sucursal Norte', 'address' => 'Norte 123']);
        $branch2 = new Branch(['name' => 'Sucursal Sur', 'phone' => '+56944444444']);
        $branchEmpty = new Branch(['name' => 'Sucursal Virtual']); // sin address ni phone

        $schema = TenantLandingSchema::make(
            $tenant,
            new Collection([$branch1, $branch2, $branchEmpty]),
            'https://example.com/cadena',
            'Desc',
            'https://example.com/og.png'
        );

        $locations = $schema[0]['location'];
        $this->assertIsArray($locations);
        $this->assertCount(2, $locations);
        $this->assertSame('Sucursal Norte', $locations[0]['name']);
        $this->assertArrayHasKey('address', $locations[0]);
        $this->assertArrayNotHasKey('telephone', $locations[0]);

        $this->assertSame('Sucursal Sur', $locations[1]['name']);
        $this->assertArrayNotHasKey('address', $locations[1]);
        $this->assertSame('+56944444444', $locations[1]['telephone']);
    }
}
