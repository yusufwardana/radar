<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchSourceJob;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class DemoIngest extends Command
{
    protected $signature = 'radar:demo:ingest {--fixture-version=1 : Demo fixture version, 1 or 2}';
    protected $description = 'Run the offline fictional RADAR demo source through the queue job synchronously.';

    public function handle(): int
    {
        $version = max(1, (int) $this->option('fixture-version'));
        $source = Source::query()->updateOrCreate(['slug' => 'demo-json-source'], [
            'name' => 'Demo JSON Source',
            'provider' => 'RADAR Development Fixture',
            'source_type' => SourceType::API,
            'poll_interval_seconds' => 60,
            'status' => SourceStatus::ACTIVE,
            'crawl_allowed' => false,
            'metadata' => ['fixture' => 'demo-json', 'fixture_version' => $version, 'demo' => true],
        ]);

        FetchSourceJob::dispatchSync($source->id);
        $this->info("Demo ingestion completed for version {$version}. Source ID: {$source->id}");
        return self::SUCCESS;
    }
}