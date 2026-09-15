<?php

declare(strict_types=1);

namespace App\Radar\Crawler;

final readonly class BrowserWorkerResult
{
    public function __construct(
        public string $requestId,
        public bool $success,
        public ?string $finalUrl,
        public ?string $title,
        public ?string $text,
        public array $links,
        public array $documents,
        public array $metadata,
        public ?string $errorCode = null,
    ) {}
}