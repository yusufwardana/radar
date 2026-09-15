<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Source;
use App\Radar\Sources\SourceAdapterResolver;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class BmkgSmokeWarning extends Command
{
    protected $signature = 'radar:bmkg:test-warning';
    protected $description = 'Fetch and summarize official BMKG nowcast RSS/CAP warnings.';

    public function handle(SourceAdapterResolver $resolver): int
    {
        $source = Source::query()->updateOrCreate(['slug' => 'bmkg-weather-warning'], [
            'name' => 'BMKG Weather Warning — Indonesia', 'provider' => 'BMKG', 'source_type' => SourceType::RSS,
            'endpoint' => 'https://www.bmkg.go.id/alerts/nowcast/id', 'poll_interval_seconds' => 120,
            'rate_limit' => 60, 'status' => SourceStatus::PAUSED,
            'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
            'metadata' => ['adapter' => 'weather-warning', 'format' => 'RSS→CAP', 'verified_at' => '2026-09-09'],
        ]);
        $adapter = $resolver->resolve($source);
        $items = $adapter->normalize($adapter->fetch());
        $this->info('BMKG Weather Warning: OK');
        foreach (array_slice($items, 0, 3) as $item) $this->line($item->title.' | '.($item->metadata['priority'] ?? 'UNKNOWN'));
        return self::SUCCESS;
    }
}