<?php

declare(strict_types=1);

namespace App\Radar\SourceHealth;

use App\Models\Source;
use App\Radar\Sources\SourceStatus;

final class SourceHealthService
{
    public function success(Source $source): void
    {
        $source->forceFill([
            'last_fetch_at' => now(),
            'last_success_at' => now(),
            'failure_count' => max(0, $source->failure_count - 1),
            'status' => $source->status === SourceStatus::DEGRADED ? SourceStatus::ACTIVE : $source->status,
        ])->save();
    }

    public function failure(Source $source): void
    {
        $failures = $source->failure_count + 1;
        $status = $failures >= (int) config('radar.health.failed_after_failures', 5)
            ? SourceStatus::FAILED
            : ($failures >= (int) config('radar.health.degraded_after_failures', 2) ? SourceStatus::DEGRADED : $source->status);

        $source->forceFill([
            'last_fetch_at' => now(),
            'last_failure_at' => now(),
            'failure_count' => $failures,
            'status' => $status,
        ])->save();
    }
}