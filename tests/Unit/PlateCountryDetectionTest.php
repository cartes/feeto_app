<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Country;
use App\Rules\LicensePlate;
use PHPUnit\Framework\TestCase;

class PlateCountryDetectionTest extends TestCase
{
    public function test_detects_argentine_mercosur_plate(): void
    {
        $this->assertSame([Country::Argentina], Country::detectFromPlate('AB123CD'));
    }

    public function test_detects_brazilian_mercosur_plate(): void
    {
        $this->assertSame([Country::Brazil], Country::detectFromPlate('ABC1D23'));
    }

    public function test_detects_bolivian_plate(): void
    {
        $this->assertSame([Country::Bolivia], Country::detectFromPlate('1234ABC'));
    }

    public function test_detects_modern_chilean_plate(): void
    {
        $this->assertSame([Country::Chile], Country::detectFromPlate('GKSB78'));
    }

    public function test_ambiguous_plate_matches_multiple_countries(): void
    {
        // ABC123 es válido en Colombia, Argentina (antiguo), Ecuador, Paraguay y Perú.
        $matches = Country::detectFromPlate('ABC123');

        $this->assertContains(Country::Colombia, $matches);
        $this->assertContains(Country::Argentina, $matches);
        $this->assertNotContains(Country::Chile, $matches);
    }

    public function test_unknown_format_matches_nothing(): void
    {
        $this->assertSame([], Country::detectFromPlate('ZZZZZZZ9'));
    }

    public function test_chilean_tenant_accepts_local_plate(): void
    {
        $this->assertPlatePasses(Country::Chile, 'GKSB78');
        $this->assertPlatePasses(Country::Chile, 'BC1234');
        $this->assertPlatePasses(Country::Chile, 'AB123');
    }

    public function test_chilean_tenant_accepts_foreign_latam_plate(): void
    {
        $this->assertPlatePasses(Country::Chile, 'AB123CD'); // Argentina Mercosur
        $this->assertPlatePasses(Country::Chile, 'ABC1D23'); // Brasil Mercosur
        $this->assertPlatePasses(Country::Chile, '1234ABC'); // Bolivia
    }

    public function test_colombian_tenant_accepts_local_and_foreign_plates(): void
    {
        $this->assertPlatePasses(Country::Colombia, 'ABC123');
        $this->assertPlatePasses(Country::Colombia, 'ABC12D');
        $this->assertPlatePasses(Country::Colombia, 'AB123CD'); // Argentina
    }

    public function test_unrecognized_plate_fails_validation(): void
    {
        $this->assertPlateFails(Country::Chile, 'ZZZZZZZ9');
        $this->assertPlateFails(Country::Chile, '12');
    }

    private function assertPlatePasses(Country $country, string $plate): void
    {
        $failed = false;

        (new LicensePlate($country))->validate('plate', $plate, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed, "Se esperaba que la patente {$plate} fuera válida para {$country->label()}.");
    }

    private function assertPlateFails(Country $country, string $plate): void
    {
        $failed = false;

        (new LicensePlate($country))->validate('plate', $plate, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed, "Se esperaba que la patente {$plate} fuera rechazada para {$country->label()}.");
    }
}
