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
     * Test OCR cleaning ("O" -> "0", "I" -> "1").
     */
    public function test_it_cleans_plate_characters(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('cleanPlate');
        $method->setAccessible(true);

        $this->assertEquals('ABCD12', $method->invoke($this->service, 'ABCD-I2'));
        $this->assertEquals('BCDF01', $method->invoke($this->service, 'BCDF O1'));
        $this->assertEquals('AB1234', $method->invoke($this->service, 'ab-1234'));
    }

    /**
     * Test modern PPU validation (4 letters + 2 numbers).
     */
    public function test_it_validates_modern_ppu(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePpu');
        $method->setAccessible(true);

        // Valid modern
        $result = $method->invoke($this->service, 'BCDF12');
        $this->assertTrue($result['valid']);
        $this->assertEquals('modern', $result['type']);

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
        $method->setAccessible(true);

        // Valid old
        $result = $method->invoke($this->service, 'AB1234');
        $this->assertTrue($result['valid']);
        $this->assertEquals('old', $result['type']);

        // Invalid old (wrong format)
        $result = $method->invoke($this->service, 'ABC123');
        $this->assertFalse($result['valid']);
    }
}
