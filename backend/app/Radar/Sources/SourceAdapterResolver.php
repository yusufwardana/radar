<?php

declare(strict_types=1);

namespace App\Radar\Sources;

use App\Models\Source;
use App\Radar\Ingestion\FetchResult;
use App\Radar\Ingestion\HttpTransport;
use App\Radar\Crawler\BrowserWorkerClient;
use App\Radar\Security\PublicUrlValidator;
use InvalidArgumentException;
use Radar\Adapters\JsonApiAdapter;
use Radar\Adapters\RssAdapter;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\SourceDefinition;
use App\Radar\Providers\Bmkg\BmkgEarthquakeAdapter;
use App\Radar\Providers\Bmkg\BmkgWeatherWarningAdapter;

final class SourceAdapterResolver
{
    public function __construct(private readonly HttpTransport $transport) {}

    public function resolve(Source $source): SourceAdapter
    {
        $definition = new SourceDefinition(
            slug: $source->slug,
            name: $source->name,
            provider: $source->provider,
            sourceType: $source->source_type->value,
            endpoint: $source->endpoint,
            categories: $source->categories ?? [],
            attribution: $source->attribution,
            termsUrl: $source->terms_url,
            crawlAllowed: (bool) $source->crawl_allowed,
            status: $source->status->value,
        );

        $fixture = $source->metadata['fixture'] ?? null;
        if ($fixture !== null) {
            return $this->fixtureAdapter($definition, (string) $fixture, $source->metadata['fixture_version'] ?? 1);
        }

        $bmkgAdapter = $source->metadata['adapter'] ?? null;
        if ($bmkgAdapter === 'earthquake') {
            return new BmkgEarthquakeAdapter($definition, $this->transport);
        }
        if ($bmkgAdapter === 'weather-warning') {
            return new BmkgWeatherWarningAdapter($definition, $this->transport);
        }

        $endpoint = $source->endpoint;
        if ($endpoint === null) {
            throw new InvalidArgumentException('Source has no configured endpoint or fixture.');
        }

        if ($source->source_type === SourceType::BROWSER) {
            return new BrowserSourceAdapter(
                $source->id,
                $source->slug,
                $endpoint,
                new BrowserWorkerClient(),
                new PublicUrlValidator(),
                isset($source->metadata['browser_fixture']) ? (string) $source->metadata['browser_fixture'] : null,
            );
        }

        return match ($source->source_type) {
            SourceType::API, SourceType::OPEN_DATA => new JsonApiAdapter($definition, function () use ($endpoint): mixed {
                return $this->transport->get($endpoint, 'application/json')->payload;
            }),
            SourceType::RSS, SourceType::ATOM, SourceType::CAP => new RssAdapter($definition, function () use ($endpoint): mixed {
                return $this->transport->get($endpoint, 'xml')->payload;
            }),
            default => throw new InvalidArgumentException('Source type requires a browser adapter: '.$source->source_type->value),
        };
    }

    public function lastFetchResult(): ?FetchResult
    {
        return $this->transport->lastResult();
    }

    private function fixtureAdapter(SourceDefinition $definition, string $fixture, mixed $version): SourceAdapter
    {
        if ($fixture === 'demo-json') {
            return new JsonApiAdapter($definition, fn (): array => ['data' => [[
                'id' => 'demo-event-1',
                'title' => 'Demo public event',
                'description' => ((int) $version >= 2 ? 'Date: 2026-09-12' : 'Date: 2026-09-10'),
                'url' => 'https://demo.invalid/events/demo-event-1',
                'published_at' => ((int) $version >= 2 ? '2026-09-12T09:00:00Z' : '2026-09-10T09:00:00Z'),
            ]]]);
        }

        if ($fixture === 'demo-rss') {
            $date = (int) $version >= 2 ? 'Sat, 12 Sep 2026 09:00:00 GMT' : 'Thu, 10 Sep 2026 09:00:00 GMT';
            return new RssAdapter($definition, fn (): string => '<rss><channel><item><title>Demo RSS announcement</title><link>https://demo.invalid/rss/1</link><description>Demo date update</description><pubDate>'.$date.'</pubDate></item></channel></rss>');
        }

        throw new InvalidArgumentException('Unknown demo fixture: '.$fixture);
    }
}