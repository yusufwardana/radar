<?php

declare(strict_types=1);

namespace Radar\Domain;

final readonly class NormalizedItem
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $externalId,
        public string $title,
        public string $text,
        public ?string $url,
        public ?string $publishedAt,
        public array $metadata = [],
    ) {}

    public function normalizedHash(): string
    {
        $canonical = [
            'external_id' => $this->externalId,
            'title' => Normalizer::text($this->title),
            'text' => Normalizer::text($this->text),
            'url' => Normalizer::url($this->url),
            'published_at' => $this->publishedAt,
            'metadata' => Normalizer::value($this->metadata),
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}