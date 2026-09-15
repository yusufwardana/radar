<?php

declare(strict_types=1);

namespace Radar\Adapters;

use Closure;
use InvalidArgumentException;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\NormalizedItem;
use Radar\Domain\SourceDefinition;

final class JsonApiAdapter implements SourceAdapter
{
    /** @param callable(): mixed $fetcher */
    public function __construct(
        private readonly SourceDefinition $definition,
        callable $fetcher,
    ) {
        $this->fetcher = Closure::fromCallable($fetcher);
    }

    /** @var Closure(): mixed */
    private readonly Closure $fetcher;

    public function fetch(): mixed
    {
        return ($this->fetcher)();
    }

    public function normalize(mixed $payload): array
    {
        if (!is_array($payload)) {
            throw new InvalidArgumentException('JSON source payload must be an array.');
        }

        $items = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? $item['external_id'] ?? '');
            $title = trim((string) ($item['title'] ?? $item['name'] ?? ''));
            if ($id === '' || $title === '') {
                continue;
            }
            $normalized[] = new NormalizedItem(
                externalId: $id,
                title: $title,
                text: (string) ($item['description'] ?? $item['summary'] ?? $item['text'] ?? ''),
                url: isset($item['url']) ? (string) $item['url'] : null,
                publishedAt: isset($item['published_at']) ? (string) $item['published_at'] : null,
                metadata: $item,
            );
        }

        return $normalized;
    }

    public function source(): string
    {
        return $this->definition->slug;
    }
}