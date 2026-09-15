<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FetchLog;
use App\Models\Source;
use App\Radar\Ingestion\ProcessNormalizedItem;
use App\Radar\Sources\SourceAdapterResolver;
use App\Radar\SourceHealth\SourceHealthService;
use App\Radar\Sources\SourceStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FetchSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    public function __construct(public readonly int $sourceId) {}

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(
        SourceAdapterResolver $resolver,
        ProcessNormalizedItem $processor,
        SourceHealthService $health,
    ): void {
        $source = Source::query()->find($this->sourceId);
        if ($source === null || $source->status !== SourceStatus::ACTIVE) return;

        $lock = Cache::lock("radar:source:{$source->id}:fetch", 120);
        if (! $lock->get()) return;

        $started = now();
        $fetchLog = FetchLog::create([
            'source_id' => $source->id,
            'status' => 'STARTED',
            'attempt' => $this->attempts(),
            'started_at' => $started,
            'fetched_at' => $started,
        ]);

        try {
            $adapter = $resolver->resolve($source);
            $items = $adapter->normalize($adapter->fetch());
            $transportResult = $resolver->lastFetchResult();
            $changeCount = 0;
            foreach ($items as $item) {
                if ($processor->handle($source, $item) !== null) $changeCount++;
            }
            $duration = (int) $started->diffInMilliseconds(now());
            $fetchLog->update([
                'status' => 'SUCCESS',
                'duration_ms' => $duration,
                'latency_ms' => $duration,
                'http_status' => $transportResult?->status,
                'content_type' => $transportResult?->contentType,
                'response_size' => $transportResult?->responseSize,
                'finished_at' => now(),
                'metadata' => ['normalized_count' => count($items), 'change_count' => $changeCount],
            ]);
            $health->success($source->fresh());
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $status = str_starts_with($message, 'RATE_LIMITED:') ? 'RATE_LIMITED' : 'FAILED';
            $fetchLog->update([
                'status' => $status,
                'error_code' => $status === 'RATE_LIMITED' ? 'UPSTREAM_RATE_LIMITED' : class_basename($exception),
                'error_message' => $message,
                'duration_ms' => (int) $started->diffInMilliseconds(now()),
                'finished_at' => now(),
            ]);
            $health->failure($source->fresh());
            throw $exception;
        } finally {
            optional($lock)->release();
        }
    }
}