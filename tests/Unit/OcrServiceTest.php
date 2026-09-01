<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Ai\Agents\PatentRecognitionAgent;
use App\Services\OcrService;
use PHPUnit\Framework\TestCase;

class OcrServiceTest extends TestCase
{
    protected OcrService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Agent since we only want to test the cleaning/validation logic here
        $agent = $this->createMock(PatentRecognitionAgent::class);
        $this->service = new OcrService($agent);
    }

    /**
     * Test OCR cleaning (mayúsculas, solo alfanuméricos; sin sustituir O/I,
     * que son letras válidas en patentes extranjeras).
     */
    public function test_it_cleans_plate_characters(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('cleanPlate');

        $this->assertEquals('ABCDI2', $method->invoke($this->service, 'ABCD-I2'));
        $this->assertEquals('BCDFO1', $method->invoke($this->service, 'BCDF O1'));
        $this->assertEquals('AB1234', $method->invoke($this->service, 'ab-1234'));
    }

    /**
     * Test corrección de confusiones típicas de OCR ("O" -> "0", "I" -> "1").
     */
    public function test_it_applies_ocr_corrections(): void
    {
        $this->assertEquals('ABCD12', $this->service->applyOcrCorrections('ABCDI2'));
        $this->assertEquals('BCDF01', $this->service->applyOcrCorrections('BCDFO1'));
    }

    /**
     * Test modern PPU validation (4 letters + 2 numbers).
     */
    public function test_it_validates_modern_ppu(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePpu');

        // Valid modern
        $result = $method->invoke($this->service, 'BCDF12');
        $this->assertTrue($result['valid']);
        $this->assertEquals('moderna', $result['type']);

        // Invalid modern (contains vowel) - The user regex [BCDFGHJKLPRSTVWXYZ] correctly excludes vowels
        $result = $method->invoke($this->service, 'AEIO12');
        $this->assertFalse($result['valid']);
    }

    /**
     * Test old PPU validation (2 letters + 4 numbers).
     */
    public function test_it_validates_old_ppu(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePpu');

        // Valid old
        $result = $method->invoke($this->service, 'AB1234');
        $this->assertTrue($result['valid']);
        $this->assertEquals('antigua', $result['type']);

        // ABC123 ya no es formato chileno, pero sí internacional (Colombia/Argentina)
        $result = $method->invoke($this->service, 'ABC123');
        $this->assertTrue($result['valid']);
        $this->assertEquals('internacional', $result['type']);
    }

    /**
     * Test international plate validation (foreign LATAM formats).
     */
    public function test_it_validates_international_plates(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePpu');

        // Argentina Mercosur
        $result = $method->invoke($this->service, 'AB123CD');
        $this->assertTrue($result['valid']);
        $this->assertEquals('internacional', $result['type']);

        // Brasil Mercosur
        $result = $method->invoke($this->service, 'ABC1D23');
        $this->assertTrue($result['valid']);
        $this->assertEquals('internacional', $result['type']);

        // Bolivia
        $result = $method->invoke($this->service, '1234ABC');
        $this->assertTrue($result['valid']);
        $this->assertEquals('internacional', $result['type']);

        // Sin formato reconocible
        $result = $method->invoke($this->service, 'ZZZZZZZ9');
        $this->assertFalse($result['valid']);
    }

    /**
     * Test motorcycle PPU validation (3 letters + 2 numbers, or 2 letters + 3 numbers).
     */
    public function test_it_validates_motorcycle_ppu(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePpu');

        // Valid modern motorcycle
        $result = $method->invoke($this->service, 'BCD12');
        $this->assertTrue($result['valid']);
        $this->assertEquals('moto_moderna', $result['type']);

        // Valid old motorcycle
        $result = $method->invoke($this->service, 'AB123');
        $this->assertTrue($result['valid']);
        $this->assertEquals('moto_antigua', $result['type']);

        // Invalid (too short)
        $result = $method->invoke($this->service, 'BC12');
        $this->assertFalse($result['valid']);
    }
}
