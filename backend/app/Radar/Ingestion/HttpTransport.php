<?php

declare(strict_types=1);

namespace App\Radar\Ingestion;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HttpTransport
{
    private ?FetchResult $lastResult = null;

    public function get(string $url, string $expectedContentType = 'application/json'): FetchResult
    {
        $started = microtime(true);

        try {
            $response = Http::connectTimeout((int) config('radar.http.connect_timeout', 5))
                ->timeout((int) config('radar.http.timeout', 20))
                ->retry((int) config('radar.http.retries', 2), (int) config('radar.http.retry_delay_ms', 250), throw: false)
                ->withHeaders(['User-Agent' => config('radar.http.user_agent', 'RADAR/1.0 public-intelligence')])
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('UPSTREAM_TIMEOUT', previous: $exception);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('RATE_LIMITED:'.($response->header('Retry-After') ?? 'unknown'));
        }
        if ($response->failed()) {
            throw new RequestException($response);
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($expectedContentType !== '' && !str_contains($contentType, $expectedContentType)) {
            throw new RuntimeException('UNEXPECTED_CONTENT_TYPE');
        }

        $body = $response->body();
        $maxBytes = (int) config('radar.http.max_response_bytes', 5_000_000);
        if (strlen($body) > $maxBytes) {
            throw new RuntimeException('RESPONSE_TOO_LARGE');
        }

        $payload = str_contains($expectedContentType, 'json') ? $response->json() : $body;
        if ($payload === null && str_contains($expectedContentType, 'json')) {
            throw new RuntimeException('INVALID_JSON');
        }

        return $this->lastResult = new FetchResult($payload, $response->status(), $contentType, strlen($body), [
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }

    public function lastResult(): ?FetchResult
    {
        return $this->lastResult;
    }
}