<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\ColombianPlate;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ColombianPlateRuleTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function plateProvider(): array
    {
        return [
            'auto formato estándar' => ['ABC123', true],
            'auto otra combinación' => ['XYZ789', true],
            'auto letras bajas' => ['abc123', false],
            'moto formato moderno' => ['ABC12D', true],
            'moto otra combinación' => ['XYZ99A', true],
            'motocarro' => ['123ABC', true],
            'diplomática / consular' => ['CD1234', true],
            'remolque / semirremolque' => ['R12345', true],
            'moto o formato antiguo' => ['AB123', true],
            'muy corta' => ['AB1', false],
            'muy larga' => ['ABCD1234', false],
            'solo letras' => ['ABCDEF', false],
            'solo números' => ['123456', false],
            'formato chileno nuevo 4L2N' => ['GKSB78', false],
            'con guion' => ['ABC-123', false],
            'con espacios' => ['ABC 123', false],
            'moto letras bajas' => ['abc12d', false],
        ];
    }

    #[DataProvider('plateProvider')]
    public function test_valida_placas_colombianas_de_auto_y_moto(string $plate, bool $expected): void
    {
        $validator = Validator::make(['plate' => $plate], ['plate' => [new ColombianPlate]]);

        $this->assertSame($expected, $validator->passes(), "Placa evaluada: {$plate}");
    }
}
