<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BmkgSignalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->resource->metadata ?? null) ? $this->resource->metadata : (json_decode((string) ($this->resource->metadata ?? '{}'), true) ?: []);
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'signal_type' => $this->type,
            'priority' => $this->priority,
            'confidence_score' => $this->confidence_score,
            'importance_score' => $this->importance_score,
            'event_time' => $metadata['event_time'] ?? $this->first_detected_at?->toISOString(),
            'latitude' => $metadata['latitude'] ?? ($this->latitude !== null ? (float) $this->latitude : null),
            'longitude' => $metadata['longitude'] ?? ($this->longitude !== null ? (float) $this->longitude : null),
            'magnitude' => isset($metadata['magnitude']) ? (float) $metadata['magnitude'] : null,
            'depth_km' => $metadata['depth_km'] ?? null,
            'region' => $metadata['region'] ?? null,
            'felt' => !empty($metadata['felt_description']),
            'felt_description' => $metadata['felt_description'] ?? null,
            'potential' => $metadata['potential'] ?? null,
            'tsunami_status' => $metadata['tsunami_status'] ?? null,
            'headline' => $this->title,
            'severity' => $metadata['severity'] ?? null,
            'urgency' => $metadata['urgency'] ?? null,
            'certainty' => $metadata['certainty'] ?? null,
            'area_description' => $metadata['area_description'] ?? null,
            'effective' => $metadata['effective'] ?? null,
            'expires' => $metadata['expires'] ?? null,
            'geometry' => $metadata['geometry'] ?? null,
            'source' => [
                'provider' => $metadata['provider'] ?? 'BMKG',
                'attribution' => $metadata['attribution'] ?? null,
                'url' => $metadata['source_url'] ?? null,
            ],
            'first_detected_at' => $this->first_detected_at?->toISOString(),
            'last_updated_at' => $this->last_updated_at?->toISOString(),
            'timeline' => $this->whenLoaded('events', fn (): array => $this->events->map(fn ($event): array => [
                'type' => $event->event_type, 'summary' => $event->summary, 'occurred_at' => $event->occurred_at?->toISOString(),
            ])->values()->all()),
        ];
    }
}