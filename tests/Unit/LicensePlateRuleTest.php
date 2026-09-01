<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Country;
use App\Rules\LicensePlate;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LicensePlateRuleTest extends TestCase
{
    /**
     * @return array<string, array{Country, string, bool}>
     */
    public static function plateProvider(): array
    {
        return [
            'chile auto nuevo válido' => [Country::Chile, 'GKSB78', true],
            'chile auto antiguo válido' => [Country::Chile, 'BC1234', true],
            'chile moto nueva válida' => [Country::Chile, 'ABC12', true],
            'chile placa colombiana coincide en formato 6 chars' => [Country::Chile, 'ABC123', true],
            'chile placa moto colombiana rechazada' => [Country::Chile, 'ABC12D', true],
            'colombia auto válido' => [Country::Colombia, 'ABC123', true],
            'colombia moto válida' => [Country::Colombia, 'ABC12D', true],
            'colombia motocarro válido' => [Country::Colombia, '123ABC', true],
            'colombia diplomática válida' => [Country::Colombia, 'CD1234', true],
            'colombia remolque válido' => [Country::Colombia, 'R12345', true],
            'colombia placa chilena aceptada como extranjera' => [Country::Colombia, 'GKSB78', true],
            'colombia formato inválido alfanumérico mezclado' => [Country::Colombia, 'A1B2C3', false],
            'chile placa argentina mercosur aceptada' => [Country::Chile, 'AB123CD', true],
            'chile placa brasileña mercosur aceptada' => [Country::Chile, 'ABC1D23', true],
            'chile placa boliviana aceptada' => [Country::Chile, '1234ABC', true],
            'chile formato desconocido rechazado' => [Country::Chile, 'ZZZZZZZ9', false],
        ];
    }

    #[DataProvider('plateProvider')]
    public function test_valida_placa_segun_pais(Country $country, string $plate, bool $expected): void
    {
        $validator = Validator::make(['plate' => $plate], ['plate' => [new LicensePlate($country)]]);

        $this->assertSame($expected, $validator->passes(), "País: {$country->label()}, Placa: {$plate}");
    }
}
