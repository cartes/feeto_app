<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Country;
use App\Rules\ValidIdentification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CountryIdentificationTest extends TestCase
{
    public function test_chilean_rut_validation_valid(): void
    {
        // Válidos sin puntos
        $this->assertTrue(Country::Chile->validateIdentification('11111111-1'));
        $this->assertTrue(Country::Chile->validateIdentification('12345678-5'));
        $this->assertTrue(Country::Chile->validateIdentification('11111112-K'));
        $this->assertTrue(Country::Chile->validateIdentification('7654321-6'));

        // Válidos con puntos o sin guión (se limpian)
        $this->assertTrue(Country::Chile->validateIdentification('11.111.111-1'));
        $this->assertTrue(Country::Chile->validateIdentification('123456785'));
    }

    public function test_chilean_rut_validation_invalid(): void
    {
        // Dígito verificador incorrecto
        $this->assertFalse(Country::Chile->validateIdentification('11111111-2'));
        $this->assertFalse(Country::Chile->validateIdentification('12345678-9'));
        $this->assertFalse(Country::Chile->validateIdentification('5555555-0'));
        $this->assertFalse(Country::Chile->validateIdentification('abc'));
        $this->assertFalse(Country::Chile->validateIdentification('123'));
    }

    public function test_chilean_rut_formatting_without_dots(): void
    {
        $this->assertSame('12345678-5', Country::Chile->formatIdentification('12.345.678-5'));
        $this->assertSame('11111111-1', Country::Chile->formatIdentification('111111111'));
        $this->assertSame('11111112-K', Country::Chile->formatIdentification('11.111.112-k'));
        // Formateo progresivo desde 4 dígitos
        $this->assertSame('123', Country::Chile->formatIdentification('123'));
        $this->assertSame('123-4', Country::Chile->formatIdentification('1234'));
        $this->assertSame('1234-5', Country::Chile->formatIdentification('12345'));
    }

    public function test_colombian_nit_and_cedula_validation(): void
    {
        // Cédula (numérica de 5 a 11 dígitos)
        $this->assertTrue(Country::Colombia->validateIdentification('12345678'));
        $this->assertTrue(Country::Colombia->validateIdentification('1020304050'));

        // NIT con DV DIAN
        // 900123456-8 (sum=586, rem=3, 11-3=8)
        $this->assertTrue(Country::Colombia->validateIdentification('900123456-8'));
        // NIT con DV incorrecto
        $this->assertFalse(Country::Colombia->validateIdentification('900123456-9'));
    }

    public function test_argentine_cuit_validation(): void
    {
        // CUIT 20-12345678-6 (sum=148, rem=6)
        $this->assertTrue(Country::Argentina->validateIdentification('20123456786'));
        $this->assertFalse(Country::Argentina->validateIdentification('20123456780'));
        // DNI
        $this->assertTrue(Country::Argentina->validateIdentification('12345678'));
    }

    public function test_peruvian_ruc_and_dni_validation(): void
    {
        // DNI 8 dígitos
        $this->assertTrue(Country::Peru->validateIdentification('12345678'));
        $this->assertFalse(Country::Peru->validateIdentification('123'));

        // RUC 11 dígitos
        // 20452394580 -> sum = 5*2+4*0+3*4+2*5+7*2+6*3+5*9+4*4+3*5+2*8 = 10+0+12+10+14+18+45+16+15+16 = 156. 156%11 = 2. 11-2 = 9. So 20452394589
        $this->assertTrue(Country::Peru->validateIdentification('20452394589'));
    }

    public function test_brazilian_cpf_validation(): void
    {
        // CPF válido: 111.444.777-35 (o sin puntos: 11144477735)
        // Probamos un CPF matemático estándar
        $this->assertFalse(Country::Brazil->validateIdentification('11111111111')); // mismo dígito repetido inválido
        $this->assertFalse(Country::Brazil->validateIdentification('12345678900'));
    }

    public function test_valid_identification_rule_with_validator(): void
    {
        $validator = Validator::make(
            ['rut' => '11111111-1'],
            ['rut' => [new ValidIdentification(Country::Chile)]]
        );
        $this->assertTrue($validator->passes());

        $failedValidator = Validator::make(
            ['rut' => '11111111-9'],
            ['rut' => [new ValidIdentification(Country::Chile)]]
        );
        $this->assertTrue($failedValidator->fails());
    }
}
