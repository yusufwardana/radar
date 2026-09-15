<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Locations\OfficialDatasetUrlValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocationAcquisitionTest extends TestCase
{
    public function test_only_official_kemendagri_hosts_are_allowed(): void
    {
        $validator = new OfficialDatasetUrlValidator();
        self::assertSame('https://ditjenbinaadwil.kemendagri.go.id/file.csv', $validator->validate('https://ditjenbinaadwil.kemendagri.go.id/file.csv'));
        $this->expectException(InvalidArgumentException::class);
        $validator->validate('https://example.com/file.csv');
    }

    public function test_localhost_is_not_an_official_dataset_host(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new OfficialDatasetUrlValidator())->validate('http://127.0.0.1/file.csv');
    }
}