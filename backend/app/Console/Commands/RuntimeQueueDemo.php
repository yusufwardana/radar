<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchSourceJob;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class RuntimeQueueDemo extends Command
{
    protected $signature = 'radar:runtime:queue-demo';
    protected $description = 'Dispatch the deterministic demo source to the configured queue.';

    public function handle(): int
    {
        $source = Source::query()->updateOrCreate(['slug' => 'runtime-queue-demo'], [
            'name' => 'Runtime Queue Demo Source',
            'provider' => 'RADAR Development Fixture',
            'source_type' => SourceType::API,
            'poll_interval_seconds' => 60,
            'status' => SourceStatus::ACTIVE,
            'metadata' => ['fixture' => 'demo-json', 'fixture_version' => 1, 'demo' => true],
            'last_fetch_at' => null,
        ]);

        FetchSourceJob::dispatch($source->id)->onQueue('radar-fetch');
        $this->info("Dispatched FetchSourceJob source_id={$source->id} queue=radar-fetch");
        return self::SUCCESS;
    }
}