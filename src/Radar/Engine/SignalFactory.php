<?php

declare(strict_types=1);

namespace Radar\Engine;

use Radar\Domain\Change;
use Radar\Domain\Signal;

final class SignalFactory
{
    public function fromChange(Change $change): Signal
    {
        $item = $change->current->item;
        // The fingerprint identifies the source item/event, not one transition.
        // Updates must evolve the existing Signal instead of creating a new one.
        $fingerprint = hash('sha256', $change->current->source.'|'.$change->externalId);
        $priority = (string) ($item->metadata['priority'] ?? ($change->type === 'NEW_ITEM' ? 'MEDIUM' : 'HIGH'));

        return new Signal(
            fingerprint: $fingerprint,
            title: $item->title,
            summary: $item->text !== '' ? $item->text : ($change->type === 'NEW_ITEM' ? 'New public item detected.' : 'Public information changed.'),
            type: $change->type === 'NEW_ITEM' ? 'NEW' : 'CHANGE',
            priority: $priority,
            confidenceScore: 80,
            importanceScore: $change->type === 'REMOVED_ITEM' ? 40 : 60,
            sources: [[
                'source' => $change->current->source,
                'url' => $item->url,
                'external_id' => $change->externalId,
            ]],
        );
    }
}