<?php

declare(strict_types=1);

namespace App\Radar\Ingestion;

final readonly class FetchResult
{
    public function __construct(
        public mixed $payload,
        public int $status = 200,
        public ?string $contentType = null,
        public int $responseSize = 0,
        public array $metadata = [],
    ) {}
}