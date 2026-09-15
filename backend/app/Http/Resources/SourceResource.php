<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'provider' => $this->provider,
            'source_type' => $this->source_type?->value,
            'status' => $this->status?->value,
            'attribution' => $this->attribution,
            'terms_url' => $this->terms_url,
            'last_success_at' => $this->last_success_at?->toISOString(),
            'last_failure_at' => $this->last_failure_at?->toISOString(),
            'failure_count' => $this->failure_count,
        ];
    }
}