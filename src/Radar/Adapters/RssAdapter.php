<?php

declare(strict_types=1);

namespace Radar\Adapters;

use Closure;
use InvalidArgumentException;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\NormalizedItem;
use Radar\Domain\SourceDefinition;

final class RssAdapter implements SourceAdapter
{
    /** @param callable(): string $fetcher */
    public function __construct(
        private readonly SourceDefinition $definition,
        callable $fetcher,
    ) {
        $this->fetcher = Closure::fromCallable($fetcher);
    }

    /** @var Closure(): string */
    private readonly Closure $fetcher;

    public function fetch(): mixed
    {
        return ($this->fetcher)();
    }

    public function normalize(mixed $payload): array
    {
        if (!is_string($payload) || trim($payload) === '') {
            throw new InvalidArgumentException('RSS payload must be non-empty XML.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NONET);
        if ($xml === false) {
            throw new InvalidArgumentException('RSS payload is malformed XML.');
        }

        $items = [];
        foreach (($xml->channel->item ?? []) as $item) {
            $title = trim((string) $item->title);
            $url = trim((string) $item->link);
            if ($title === '' || $url === '') {
                continue;
            }
            $items[] = new NormalizedItem(
                externalId: hash('sha256', $url),
                title: $title,
                text: trim((string) ($item->description ?? '')),
                url: $url,
                publishedAt: trim((string) ($item->pubDate ?? '')) ?: null,
                metadata: ['guid' => trim((string) ($item->guid ?? ''))],
            );
        }

        return $items;
    }

    public function source(): string
    {
        return $this->definition->slug;
    }
}