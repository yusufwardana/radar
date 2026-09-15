<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Locations\SqlLocationDatasetParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SqlLocationDatasetParserTest extends TestCase
{
    public function test_parses_only_data_and_preserves_adm4_as_unresolved(): void
    {
        $records = iterator_to_array((new SqlLocationDatasetParser())->records(__DIR__.'/../Fixtures/location-hierarchy.sql'));
        self::assertCount(4, $records);
        self::assertSame('01.02.03.2001', $records[3]['code']);
        self::assertSame('ADM4', $records[3]['level']);
    }

    public function test_rejects_unsupported_sql_constructs(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'radar-sql-');
        file_put_contents($file, 'ALTER TABLE wilayah ADD bad text;');
        try { $this->expectException(RuntimeException::class); iterator_to_array((new SqlLocationDatasetParser())->records($file)); } finally { @unlink($file); }
    }
}