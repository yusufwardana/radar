<?php

declare(strict_types=1);

namespace Radar\Engine;

use DateTimeImmutable;
use Radar\Domain\Change;
use Radar\Domain\Snapshot;

final class ChangeEngine
{
    /** @param array<string, Snapshot> $previous */
    /** @param list<Snapshot> $current */
    /** @return list<Change> */
    public function compare(array $previous, array $current): array
    {
        $changes = [];
        foreach ($current as $snapshot) {
            $old = $previous[$snapshot->item->externalId] ?? null;
            if ($old === null) {
                $changes[] = new Change('NEW_ITEM', $snapshot->item->externalId, null, $snapshot);
                continue;
            }
            if ($old->normalizedHash === $snapshot->normalizedHash) {
                continue;
            }
            $type = $old->item->publishedAt !== $snapshot->item->publishedAt
                ? 'DATE_CHANGED'
                : 'TEXT_CHANGED';
            $changes[] = new Change($type, $snapshot->item->externalId, $old, $snapshot, [
                'old_hash' => $old->normalizedHash,
                'new_hash' => $snapshot->normalizedHash,
            ]);
        }

        $currentIds = array_fill_keys(array_map(fn (Snapshot $s): string => $s->item->externalId, $current), true);
        foreach ($previous as $id => $old) {
            if (!isset($currentIds[$id])) {
                $changes[] = new Change('REMOVED_ITEM', $id, $old, new Snapshot(
                    $old->source,
                    $old->item,
                    $old->normalizedHash,
                    new DateTimeImmutable(),
                ));
            }
        }

        return $changes;
    }
}