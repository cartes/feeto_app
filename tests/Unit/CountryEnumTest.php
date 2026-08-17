<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Country;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CountryEnumTest extends TestCase
{
    /**
     * @return array<string, array{Country, string, string, string, string}>
     */
    public static function countryProvider(): array
    {
        return [
            'Chile' => [Country::Chile, 'Chile', '🇨🇱', '+56', '+56 9 1234 5678'],
            'Colombia' => [Country::Colombia, 'Colombia', '🇨🇴', '+57', '+57 300 123 4567'],
        ];
    }

    #[DataProvider('countryProvider')]
    public function test_country_enum_helpers(
        Country $country,
        string $expectedLabel,
        string $expectedFlag,
        string $expectedPhonePrefix,
        string $expectedPhonePlaceholder,
    ): void {
        $this->assertSame($expectedLabel, $country->label());
        $this->assertSame($expectedFlag, $country->flag());
        $this->assertSame($expectedPhonePrefix, $country->phonePrefix());
        $this->assertSame($expectedPhonePlaceholder, $country->phonePlaceholder());
    }

    public function test_iso_code_returns_value(): void
    {
        $this->assertSame('CL', Country::Chile->isoCode());
        $this->assertSame('CO', Country::Colombia->isoCode());
    }

    public function test_all_cases_are_covered(): void
    {
        $cases = Country::cases();

        $this->assertCount(2, $cases);
        $this->assertSame('CL', $cases[0]->value);
        $this->assertSame('CO', $cases[1]->value);
    }
}
