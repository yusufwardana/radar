<?php

declare(strict_types=1);

namespace App\Radar\Signals;

use App\Models\Change as ChangeModel;
use App\Models\Signal as SignalModel;
use App\Models\SignalEvent;
use App\Models\Snapshot as SnapshotModel;
use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Radar\Domain\Change;
use Radar\Domain\Signal;

final class SignalPersister
{
    public function persist(Source $source, Change $change, ChangeModel $changeModel): SignalModel
    {
        return DB::transaction(function () use ($source, $change, $changeModel): SignalModel {
            $signal = SignalModel::query()->firstOrCreate(
                ['fingerprint' => (new \Radar\Engine\SignalFactory())->fromChange($change)->fingerprint],
                $this->attributes((new \Radar\Engine\SignalFactory())->fromChange($change)),
            );

            $factorySignal = (new \Radar\Engine\SignalFactory())->fromChange($change);
            $signal->fill([
                'title' => $factorySignal->title,
                'summary' => $factorySignal->summary,
                'type' => $factorySignal->type,
                'priority' => $factorySignal->priority,
                'last_updated_at' => now(),
                'confidence_score' => $factorySignal->confidenceScore,
                'importance_score' => $factorySignal->importanceScore,
                'latitude' => $change->current->item->metadata['latitude'] ?? null,
                'longitude' => $change->current->item->metadata['longitude'] ?? null,
                'metadata' => array_merge($change->current->item->metadata, [
                    'last_change_id' => $changeModel->id,
                    'change_type' => $change->type,
                ]),
            ])->save();

            $existingPivot = DB::table('signal_sources')->where(['signal_id' => $signal->id, 'source_id' => $source->id])->exists();
            $signal->sources()->syncWithoutDetaching([$source->id => [
                'source_title' => $change->current->item->title,
                'source_url' => $change->current->item->url,
                'first_detected_at' => $signal->first_detected_at,
                'last_checked_at' => now(),
                'attribution' => $source->attribution,
                'metadata' => json_encode(['external_id' => $change->externalId], JSON_THROW_ON_ERROR),
            ]]);
            $signal->update(['source_count' => $signal->sources()->count()]);

            $eventType = $existingPivot ? 'CONTENT_CHANGED' : 'FIRST_DETECTED';
            $summary = $existingPivot ? 'Source content changed.' : 'Signal first detected.';
            SignalEvent::create([
                'signal_id' => $signal->id,
                'event_type' => $eventType,
                'summary' => $summary,
                'metadata' => ['change_id' => $changeModel->id, 'change_type' => $change->type],
                'occurred_at' => now(),
            ]);

            return $signal->fresh('sources');
        });
    }

    /** @return array<string, mixed> */
    private function attributes(Signal $signal): array
    {
        return [
            'title' => $signal->title,
            'summary' => $signal->summary,
            'type' => $signal->type,
            'priority' => $signal->priority,
            'first_detected_at' => now(),
            'last_updated_at' => now(),
            'confidence_score' => $signal->confidenceScore,
            'importance_score' => $signal->importanceScore,
            'source_count' => 0,
            'status' => 'active',
            'metadata' => [],
        ];
    }
}