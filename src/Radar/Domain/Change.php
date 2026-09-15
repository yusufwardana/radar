<?php

declare(strict_types=1);

namespace Radar\Domain;

final readonly class Change
{
    public function __construct(
        public string $type,
        public string $externalId,
        public ?Snapshot $previous,
        public Snapshot $current,
        public array $details = [],
    ) {}
}