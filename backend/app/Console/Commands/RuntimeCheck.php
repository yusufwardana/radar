<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RuntimeProbeJob;
use App\Radar\Crawler\BrowserWorkerClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RuntimeCheck extends Command
{
    protected $signature = 'radar:runtime:check {--queue-probe : Dispatch a probe to the configured queue}';
    protected $description = 'Check RADAR database, cache, locks, queue configuration, and browser worker health.';

    public function handle(BrowserWorkerClient $browser): int
    {
        $failed = false;
        $failed = !$this->check('Database', function (): string {
            DB::connection()->getPdo();
            return DB::connection()->getDriverName();
        }) || $failed;
        $failed = !$this->check('Cache', function (): string {
            $key = 'radar:runtime:cache:'.Str::uuid();
            Cache::put($key, 'ok', 30);
            if (Cache::get($key) !== 'ok') throw new \RuntimeException('cache round-trip failed');
            return (string) config('cache.default');
        }) || $failed;
        $failed = !$this->check('Lock', function (): string {
            $key = 'radar:runtime:lock:'.Str::uuid();
            $first = Cache::lock($key, 10);
            if (!$first->get()) throw new \RuntimeException('first lock acquisition failed');
            $second = Cache::lock($key, 10);
            if ($second->get()) throw new \RuntimeException('overlapping lock acquisition succeeded');
            $first->release();
            $third = Cache::lock($key, 10);
            if (!$third->get()) throw new \RuntimeException('released lock could not be reacquired');
            $third->release();
            return (string) config('cache.default');
        }) || $failed;
        if ($this->option('queue-probe')) {
            $probeId = (string) Str::uuid();
            RuntimeProbeJob::dispatch($probeId)->onQueue('radar-maintenance');
            $this->line('Queue       DISPATCHED probe='.$probeId);
        } else {
            $this->line('Queue       NOT PROBED (use --queue-probe with a running worker)');
        }
        $failed = !$this->check('Browser Worker', fn (): string => $browser->health() ? 'health ok' : throw new \RuntimeException('health endpoint unavailable')) || $failed;
        $this->line('Scheduler   radar:sources:dispatch every minute');
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $label, callable $probe): bool
    {
        try { $this->line(str_pad($label, 14).'OK '.(string) $probe()); return true; }
        catch (Throwable $e) { $this->error(str_pad($label, 14).'FAILED '.$e->getMessage()); return false; }
    }
}