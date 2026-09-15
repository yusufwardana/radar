<?php

declare(strict_types=1);

namespace Radar\Domain;

final readonly class SourceDefinition
{
    /** @param list<string> $categories */
    public function __construct(
        public string $slug,
        public string $name,
        public string $provider,
        public string $sourceType,
        public ?string $endpoint = null,
        public array $categories = [],
        public ?string $attribution = null,
        public ?string $termsUrl = null,
        public bool $crawlAllowed = false,
        public string $status = 'ACTIVE',
    ) {}
}