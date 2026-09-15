<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Locations\AdministrativeCode;
use App\Radar\Locations\LocationLevel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AdministrativeCodeTest extends TestCase
{
    public function test_code_preserves_leading_zeroes_and_formats_hierarchy(): void
    {
        $code = AdministrativeCode::forLevel('01.02.03.0004', LocationLevel::KELURAHAN);
        self::assertSame('0102030004', $code->digits);
        self::assertSame('01.02.03.0004', $code->display);
        self::assertSame('01.02.03', $code->parent()?->display);
        self::assertTrue($code->isAdm4());
    }

    public function test_invalid_code_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdministrativeCode::forLevel('31.71.03.1001', LocationLevel::DISTRICT);
    }
}