<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchSourceJob;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class DemoBrowserIngest extends Command
{
    protected $signature = 'radar:demo:browser {fixture=dynamic} {--queue : Dispatch through the configured queue instead of running synchronously}';
    protected $description = 'Run a controlled Playwright fixture through the full RADAR ingestion pipeline.';

    public function handle(): int
    {
        $fixture = (string) $this->argument('fixture');
        $source = Source::query()->updateOrCreate(['slug' => 'demo-browser-source'], [
            'name' => 'Demo Browser Source',
            'provider' => 'RADAR Development Fixture',
            'source_type' => SourceType::BROWSER,
            'endpoint' => 'https://radar.test/fixtures/'.$fixture,
            'poll_interval_seconds' => 60,
            'status' => SourceStatus::ACTIVE,
            'crawl_allowed' => true,
            'metadata' => ['browser_fixture' => $fixture, 'demo' => true],
        ]);

        if ($this->option('queue')) {
            FetchSourceJob::dispatch($source->id)->onQueue('radar-browser');
            $this->info("Browser demo job dispatched for {$fixture}. Source ID: {$source->id} queue=radar-browser");
            return self::SUCCESS;
        }

        FetchSourceJob::dispatchSync($source->id);
        $this->info("Browser demo ingestion completed for {$fixture}. Source ID: {$source->id}");
        return self::SUCCESS;
    }
}