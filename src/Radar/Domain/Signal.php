<?php

declare(strict_types=1);

namespace Radar\Domain;

final readonly class Signal
{
    /** @param list<array<string, mixed>> $sources */
    public function __construct(
        public string $fingerprint,
        public string $title,
        public string $summary,
        public string $type,
        public string $priority,
        public int $confidenceScore,
        public int $importanceScore,
        public array $sources,
    ) {}
}