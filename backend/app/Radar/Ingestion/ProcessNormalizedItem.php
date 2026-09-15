<?php

declare(strict_types=1);

namespace App\Radar\Ingestion;

use App\Models\Change as ChangeModel;
use App\Models\Source;
use App\Radar\Signals\SignalPersister;
use Illuminate\Support\Facades\DB;
use Radar\Domain\Change;
use Radar\Domain\NormalizedItem;
use Radar\Engine\ChangeEngine;

final class ProcessNormalizedItem
{
    public function __construct(
        private readonly SnapshotPersister $snapshots,
        private readonly ChangeEngine $changeEngine,
        private readonly SignalPersister $signals,
    ) {}

    public function handle(Source $source, NormalizedItem $item, int $httpStatus = 200): ?ChangeModel
    {
        $snapshot = $this->snapshots->persist($source, $item, $httpStatus);
        if ($snapshot === null) return null;

        $previousModel = $this->snapshots->previous($source, $item);
        // The just-created snapshot is the latest; lookup the prior row by excluding it.
        $priorModel = $previousModel?->id === $snapshot->id
            ? $this->snapshots->previousBefore($source, $item, $snapshot->id)
            : $previousModel;
        $current = $this->snapshots->toDomain($source, $snapshot, $item);
        $previous = $priorModel === null ? [] : [$item->externalId => $this->snapshots->toDomain($source, $priorModel, new NormalizedItem(
            (string) $priorModel->external_id,
            (string) $priorModel->title,
            (string) $priorModel->text_content,
            $priorModel->canonical_url,
            $priorModel->metadata['published_at'] ?? null,
            $priorModel->metadata ?? [],
        ))];
        $changes = $this->changeEngine->compare($previous, [$current]);
        $change = $changes[0] ?? null;
        if ($change === null) return null;

        return DB::transaction(function () use ($source, $snapshot, $priorModel, $change): ChangeModel {
            $changeModel = ChangeModel::query()->firstOrCreate([
                'source_id' => $source->id,
                'current_snapshot_id' => $snapshot->id,
                'change_type' => $change->type,
            ], [
                'previous_snapshot_id' => $priorModel?->id,
                'external_id' => $change->externalId,
                'details' => $change->details,
            ]);
            $this->signals->persist($source, $change, $changeModel);
            return $changeModel;
        });
    }
}