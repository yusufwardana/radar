<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\FetchSourceJob;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RadarQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_sends_only_due_active_sources_to_radar_fetch(): void
    {
        Queue::fake();
        $due = Source::create([
            'name' => 'Due Fixture', 'slug' => 'due-fixture', 'provider' => 'Test',
            'source_type' => SourceType::API, 'status' => SourceStatus::ACTIVE,
            'poll_interval_seconds' => 60, 'metadata' => ['fixture' => 'demo-json'],
        ]);
        Source::create([
            'name' => 'Paused Fixture', 'slug' => 'paused-fixture', 'provider' => 'Test',
            'source_type' => SourceType::API, 'status' => SourceStatus::PAUSED,
            'poll_interval_seconds' => 60, 'metadata' => ['fixture' => 'demo-json'],
        ]);

        $this->artisan('radar:sources:dispatch')->assertSuccessful();

        Queue::assertPushedOn('radar-fetch', FetchSourceJob::class, fn (FetchSourceJob $job): bool => $job->sourceId === $due->id);
        Queue::assertPushed(FetchSourceJob::class, 1);
    }
}