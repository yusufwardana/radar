<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Crawler\BrowserWorkerClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrowserWorkerClientTest extends TestCase
{
    public function test_validates_a_worker_result(): void
    {
        Http::fake(['http://worker.test/v1/crawl' => Http::response([
            'request_id' => '6f82f4d0-0d6a-4d3e-a964-4b7c6d3b2f1a',
            'success' => true,
            'final_url' => 'https://example.test/page',
            'title' => 'Fixture',
            'text' => 'Fixture text',
            'links' => [], 'documents' => [], 'metadata' => [],
        ])]);
        config(['radar.browser.url' => 'http://worker.test']);

        $result = app(BrowserWorkerClient::class)->crawl(1, 'https://example.test/page');

        $this->assertSame('Fixture', $result->title);
        Http::assertSent(fn ($request): bool => $request->url() === 'http://worker.test/v1/crawl' && $request['source_id'] === 1);
    }

    public function test_rejects_an_invalid_worker_result(): void
    {
        Http::fake(['http://worker.test/v1/crawl' => Http::response(['success' => true])]);
        config(['radar.browser.url' => 'http://worker.test']);

        $this->expectException(\RuntimeException::class);
        app(BrowserWorkerClient::class)->crawl(1, 'https://example.test/page');
    }
}