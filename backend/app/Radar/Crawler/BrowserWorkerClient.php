<?php

declare(strict_types=1);

namespace App\Radar\Crawler;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class BrowserWorkerClient
{
    public function health(): bool
    {
        return Http::timeout((int) config('radar.browser.timeout', 20_000) / 1000)
            ->get(rtrim((string) config('radar.browser.url'), '/').'/health')->successful();
    }

    public function crawl(int $sourceId, string $url, array $selectors = []): BrowserWorkerResult
    {
        return $this->request($sourceId, $url, $selectors);
    }

    public function crawlFixture(int $sourceId, string $fixture): BrowserWorkerResult
    {
        if (!config('radar.browser.fixtures_enabled')) {
            throw new RuntimeException('BROWSER_FIXTURES_DISABLED');
        }
        return $this->request($sourceId, 'https://radar.test/fixtures/'.$fixture, [], 'fixture', $fixture);
    }

    private function request(int $sourceId, string $url, array $selectors = [], string $mode = 'extract', ?string $fixture = null): BrowserWorkerResult
    {
        $response = Http::timeout((int) config('radar.browser.timeout', 20_000) / 1000)
            ->post(rtrim((string) config('radar.browser.url'), '/').'/v1/crawl', [
                'request_id' => (string) Str::uuid(),
                'source_id' => $sourceId,
                'url' => $url,
                'mode' => $mode,
                'selectors' => $selectors === [] ? (object) [] : $selectors,
                'timeout_ms' => (int) config('radar.browser.timeout', 20_000),
                'fixture' => $fixture,
            ]);

        $body = $response->json();
        if (!is_array($body) || !isset($body['request_id'], $body['success'])) {
            throw new RuntimeException('INVALID_BROWSER_WORKER_RESPONSE');
        }
        if (!$response->successful() || $body['success'] !== true) {
            throw new RuntimeException((string) ($body['error_code'] ?? 'BROWSER_WORKER_REJECTED'));
        }

        return new BrowserWorkerResult(
            requestId: (string) $body['request_id'],
            success: true,
            finalUrl: isset($body['final_url']) ? (string) $body['final_url'] : null,
            title: isset($body['title']) ? (string) $body['title'] : null,
            text: isset($body['text']) ? (string) $body['text'] : null,
            links: is_array($body['links'] ?? null) ? $body['links'] : [],
            documents: is_array($body['documents'] ?? null) ? $body['documents'] : [],
            metadata: is_array($body['metadata'] ?? null) ? $body['metadata'] : [],
        );
    }
}