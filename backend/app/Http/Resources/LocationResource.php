<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type, 'level' => $this->level?->value, 'code' => $this->code, 'display_code' => $this->code, 'forecast_ready' => $this->forecast_ready, 'province' => $this->province_name, 'regency' => $this->regency_name, 'district' => $this->district_name, 'latitude' => $this->latitude !== null ? (float) $this->latitude : null, 'longitude' => $this->longitude !== null ? (float) $this->longitude : null, 'timezone' => $this->timezone, 'breadcrumbs' => array_values(array_filter([$this->province_name, $this->regency_name, $this->district_name, $this->name]))];
    }
}