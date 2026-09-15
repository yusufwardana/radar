<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'type' => $this->type,
            'category' => $this->category,
            'priority' => $this->priority,
            'location' => $this->when($this->latitude !== null, fn (): array => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ]),
            'first_detected_at' => $this->first_detected_at?->toISOString(),
            'last_updated_at' => $this->last_updated_at?->toISOString(),
            'confidence_score' => $this->confidence_score,
            'importance_score' => $this->importance_score,
            'source_count' => $this->source_count,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'sources' => SourceResource::collection($this->whenLoaded('sources')),
            'timeline' => $this->whenLoaded('events', fn (): array => $this->events->map(fn ($event): array => [
                'type' => $event->event_type,
                'summary' => $event->summary,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ])->values()->all()),
        ];
    }
}