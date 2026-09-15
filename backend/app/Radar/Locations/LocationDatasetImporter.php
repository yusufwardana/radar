<?php

declare(strict_types=1);

namespace App\Radar\Locations;

interface LocationDatasetImporter
{
    /** @return iterable<array<string,mixed>> */
    public function records(string $source): iterable;
}