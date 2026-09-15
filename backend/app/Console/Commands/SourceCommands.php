<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use Illuminate\Console\Command;

class SourceCommands extends Command
{
    protected $signature = 'radar:source {action : list|enable|disable|health} {slug?}';
    protected $description = 'Manage RADAR source activation and health safely.';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $slug = $this->argument('slug');
        if ($action === 'list') {
            Source::query()->orderBy('provider')->orderBy('slug')->get()->each(fn (Source $source) => $this->line("{$source->slug}\t{$source->status->value}\t{$source->provider}"));
            return self::SUCCESS;
        }
        if (!is_string($slug) || $slug === '') { $this->error('A source slug is required.'); return self::INVALID;
        }
        $source = Source::query()->where('slug', $slug)->first();
        if ($source === null) { $this->error("Source not found: {$slug}"); return self::FAILURE; }
        if ($action === 'enable') {
            if ($source->status === SourceStatus::DISABLED) { $this->error('DISABLED sources require explicit administrative review.'); return self::FAILURE; }
            $source->update(['status' => SourceStatus::ACTIVE]);
        } elseif ($action === 'disable') {
            $source->update(['status' => SourceStatus::DISABLED]);
        } elseif ($action === 'health') {
            $this->line("{$source->slug} status={$source->status->value} last_fetch={$source->last_fetch_at?->toISOString()} last_success={$source->last_success_at?->toISOString()} last_failure={$source->last_failure_at?->toISOString()} failures={$source->failure_count}");
            return self::SUCCESS;
        } else { $this->error('Action must be list, enable, disable, or health.'); return self::INVALID; }
        $this->info("{$source->slug}: {$source->fresh()->status->value}");
        return self::SUCCESS;
    }
}