<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Source;
use App\Radar\Sources\SourceAdapterResolver;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class BmkgSmokeEarthquake extends Command
{
    protected $signature = 'radar:bmkg:test-earthquake {--persist : Persist normalized items through the queue pipeline}';
    protected $description = 'Fetch and summarize the official BMKG latest earthquake JSON feed.';

    public function handle(SourceAdapterResolver $resolver): int
    {
        $source = Source::query()->updateOrCreate(['slug' => 'bmkg-earthquake-latest'], [
            'name' => 'BMKG Earthquake — Latest', 'provider' => 'BMKG', 'source_type' => SourceType::API,
            'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json', 'poll_interval_seconds' => 120,
            'rate_limit' => 60, 'status' => SourceStatus::PAUSED,
            'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
            'metadata' => ['adapter' => 'earthquake', 'category' => 'latest', 'verified_at' => '2026-09-09'],
        ]);
        $adapter = $resolver->resolve($source);
        $items = $adapter->normalize($adapter->fetch());
        $this->info('BMKG Earthquake: OK');
        foreach (array_slice($items, 0, 3) as $item) $this->line($item->title.' | '.$item->publishedAt);
        if ($this->option('persist')) {
            $source->update(['status' => SourceStatus::ACTIVE]);
            \App\Jobs\FetchSourceJob::dispatchSync($source->id);
            $source->update(['status' => SourceStatus::PAUSED]);
            $this->info('Persisted through RADAR ingestion, then source returned to PAUSED.');
        }
        return self::SUCCESS;
    }
}