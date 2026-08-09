<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\ChileanPlate;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChileanPlateRuleTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function plateProvider(): array
    {
        return [
            'auto formato nuevo' => ['GKSB78', true],
            'auto formato antiguo' => ['BC1234', true],
            'auto alfanumerica generica' => ['AB12CD', true],
            'moto formato nuevo' => ['ABC12', true],
            'moto formato antiguo' => ['AB123', true],
            'muy corta' => ['AB12', false],
            'muy larga' => ['GKSB789', false],
            'cinco caracteres sin formato moto' => ['A1B2C', false],
            'minusculas' => ['abc12', false],
            'con simbolos' => ['AB·123', false],
        ];
    }

    #[DataProvider('plateProvider')]
    public function test_valida_patentes_de_auto_y_moto(string $plate, bool $expected): void
    {
        $validator = Validator::make(['plate' => $plate], ['plate' => [new ChileanPlate]]);

        $this->assertSame($expected, $validator->passes(), "Patente evaluada: {$plate}");
    }
}
