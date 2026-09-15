<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Console\Command;

class BmkgRegisterSources extends Command
{
    protected $signature = 'radar:bmkg:register';
    protected $description = 'Register verified BMKG structured sources as PAUSED.';

    public function handle(): int
    {
        $sources = [
            ['slug' => 'bmkg-earthquake-latest', 'name' => 'BMKG Earthquake — Latest', 'type' => SourceType::API, 'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json', 'category' => 'latest'],
            ['slug' => 'bmkg-earthquake-m5', 'name' => 'BMKG Earthquake — M5+', 'type' => SourceType::API, 'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json', 'category' => 'm5_plus'],
            ['slug' => 'bmkg-earthquake-felt', 'name' => 'BMKG Earthquake — Felt', 'type' => SourceType::API, 'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json', 'category' => 'felt'],
            ['slug' => 'bmkg-weather-warning', 'name' => 'BMKG Weather Warning — Indonesia', 'type' => SourceType::RSS, 'endpoint' => 'https://www.bmkg.go.id/alerts/nowcast/id', 'category' => 'nowcast'],
        ];

        foreach ($sources as $source) {
            Source::query()->updateOrCreate(['slug' => $source['slug']], [
                'name' => $source['name'], 'provider' => 'BMKG', 'source_type' => $source['type'],
                'endpoint' => $source['endpoint'], 'poll_interval_seconds' => 120, 'rate_limit' => 60,
                'status' => SourceStatus::PAUSED, 'crawl_allowed' => false,
                'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
                'metadata' => ['adapter' => $source['type'] === SourceType::RSS ? 'weather-warning' : 'earthquake', 'category' => $source['category'], 'verified_at' => '2026-09-09'],
            ]);
            $this->line("Registered PAUSED: {$source['slug']}");
        }

        return self::SUCCESS;
    }
}