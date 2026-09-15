<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Source;
use App\Jobs\FetchSourceJob;
use App\Radar\Sources\SourceStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DispatchDueSources extends Command
{
    protected $signature = 'radar:sources:dispatch';
    protected $description = 'Dispatch due RADAR source fetch jobs.';

    public function handle(): int
    {
        $now = Carbon::now();
        $count = 0;
        Source::query()->where('status', SourceStatus::ACTIVE)->chunkById(100, function ($sources) use ($now, &$count): void {
            foreach ($sources as $source) {
                if ($source->last_fetch_at === null || $source->last_fetch_at->addSeconds($source->poll_interval_seconds)->lte($now)) {
                    $lock = Cache::lock("radar:source:{$source->id}:dispatch", 60);
                    if ($lock->get()) {
                        try {
                            FetchSourceJob::dispatch($source->id)->onQueue('radar-fetch');
                            $this->line("Dispatched: {$source->slug}");
                            $count++;
                        } finally {
                            $lock->release();
                        }
                    }
                }
            }
        });
        $this->info("{$count} source(s) due.");
        return self::SUCCESS;
    }
}