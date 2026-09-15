<?php

declare(strict_types=1);

namespace Radar\Domain;

use DateTimeImmutable;

final readonly class Snapshot
{
    public function __construct(
        public string $source,
        public NormalizedItem $item,
        public string $normalizedHash,
        public DateTimeImmutable $fetchedAt,
    ) {}
}