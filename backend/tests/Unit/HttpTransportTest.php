<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Ingestion\HttpTransport;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class HttpTransportTest extends TestCase
{
    public function test_uses_mocked_json_transport_and_records_response_metadata(): void
    {
        Http::fake(['https://fixture.test/*' => Http::response(['data' => []], 200, ['Content-Type' => 'application/json'])]);
        $result = app(HttpTransport::class)->get('https://fixture.test/feed');
        $this->assertSame(200, $result->status);
        $this->assertSame('application/json', $result->contentType);
        $this->assertSame([], $result->payload['data']);
    }

    public function test_rejects_unexpected_content_type(): void
    {
        Http::fake(['https://fixture.test/*' => Http::response('<html/>', 200, ['Content-Type' => 'text/html'])]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UNEXPECTED_CONTENT_TYPE');
        app(HttpTransport::class)->get('https://fixture.test/feed');
    }
}