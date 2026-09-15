<?php

declare(strict_types=1);

namespace App\Radar\Ingestion;

use App\Models\Snapshot as SnapshotModel;
use App\Models\Source;
use Radar\Domain\NormalizedItem;
use Radar\Domain\Snapshot;

final class SnapshotPersister
{
    public function previous(Source $source, NormalizedItem $item): ?SnapshotModel
    {
        $query = SnapshotModel::query()->where('source_id', $source->id);
        $query->where(function ($identity) use ($item): void {
            $identity->where('external_id', $item->externalId);
            if ($item->url !== null) {
                $identity->orWhere('canonical_url', $item->url);
            }
        });

        return $query->latest('fetched_at')->first();
    }

    public function persist(Source $source, NormalizedItem $item, int $httpStatus = 200): ?SnapshotModel
    {
        $previous = $this->previous($source, $item);
        $hash = $item->normalizedHash();
        if ($previous?->normalized_hash === $hash) {
            return null;
        }

        return SnapshotModel::create([
            'source_id' => $source->id,
            'external_id' => $item->externalId,
            'canonical_url' => $item->url,
            'content_hash' => hash('sha256', $item->text),
            'normalized_hash' => $hash,
            'title' => $item->title,
            'text_content' => $item->text,
            'metadata' => $item->metadata,
            'http_status' => $httpStatus,
            'fetched_at' => now(),
        ]);
    }

    public function previousBefore(Source $source, NormalizedItem $item, int $snapshotId): ?SnapshotModel
    {
        $query = SnapshotModel::query()->where('source_id', $source->id)->whereKeyNot($snapshotId);
        $query->where(function ($identity) use ($item): void {
            $identity->where('external_id', $item->externalId);
            if ($item->url !== null) {
                $identity->orWhere('canonical_url', $item->url);
            }
        });

        return $query->latest('fetched_at')->first();
    }

    public function toDomain(Source $source, SnapshotModel $snapshot, NormalizedItem $item): Snapshot
    {
        return new Snapshot($source->slug, $item, $snapshot->normalized_hash, $snapshot->fetched_at->toDateTimeImmutable());
    }
}