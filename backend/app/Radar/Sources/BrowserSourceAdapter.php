<?php

declare(strict_types=1);

namespace App\Radar\Sources;

use App\Radar\Crawler\BrowserWorkerClient;
use App\Radar\Security\PublicUrlValidator;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\NormalizedItem;

final class BrowserSourceAdapter implements SourceAdapter
{
    public function __construct(
        private readonly int $sourceId,
        private readonly string $sourceSlug,
        private readonly string $url,
        private readonly BrowserWorkerClient $client,
        private readonly PublicUrlValidator $validator,
        private readonly ?string $fixture = null,
    ) {}

    public function fetch(): mixed
    {
        return $this->fixture !== null
            ? $this->client->crawlFixture($this->sourceId, $this->fixture)
            : $this->client->crawl($this->sourceId, $this->validator->validate($this->url));
    }

    public function normalize(mixed $payload): array
    {
        return [new NormalizedItem(
            externalId: hash('sha256', $payload->finalUrl ?? $this->url),
            title: $payload->title ?? $this->sourceSlug,
            text: $payload->text ?? '',
            url: $payload->finalUrl ?? $this->url,
            publishedAt: null,
            metadata: ['links' => $payload->links, 'documents' => $payload->documents, ...$payload->metadata],
        )];
    }

    public function source(): string
    {
        return $this->sourceSlug;
    }
}