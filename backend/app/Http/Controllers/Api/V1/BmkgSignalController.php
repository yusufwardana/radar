<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BmkgSignalIndexRequest;
use App\Http\Resources\BmkgSignalResource;
use App\Models\Signal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BmkgSignalController extends Controller
{
    public function earthquakes(BmkgSignalIndexRequest $request)
    {
        return BmkgSignalResource::collection($this->query($request, 'earthquake')->paginate($request->integer('per_page', 25)));
    }

    public function earthquake(BmkgSignalIndexRequest $request, Signal $signal): BmkgSignalResource
    {
        abort_unless($this->isType($signal, 'earthquake'), 404);
        return new BmkgSignalResource($signal->load(['sources', 'events']));
    }

    public function alerts(BmkgSignalIndexRequest $request)
    {
        return BmkgSignalResource::collection($this->query($request, 'weather-alert')->paginate($request->integer('per_page', 25)));
    }

    public function alert(BmkgSignalIndexRequest $request, Signal $signal): BmkgSignalResource
    {
        abort_unless($this->isType($signal, 'weather-alert'), 404);
        return new BmkgSignalResource($signal->load(['sources', 'events']));
    }

    public function map(BmkgSignalIndexRequest $request)
    {
        $layers = array_filter(explode(',', (string) $request->query('layers', 'earthquake,weather-alert')));
        abort_if(array_diff($layers, ['earthquake', 'weather-alert']) !== [], 422, 'Unsupported map layer.');
        $query = $this->query($request, null)->limit(500);
        return response()->json(['data' => $query->get()->map(function (Signal $signal) use ($layers): ?array {
            $metadata = $signal->metadata ?? [];
            $layer = $this->layer($metadata);
            if (!in_array($layer, $layers, true)) return null;
            return $layer === 'earthquake' ? [
                'id' => $signal->id, 'layer' => $layer, 'lat' => (float) $metadata['latitude'], 'lng' => (float) $metadata['longitude'],
                'magnitude' => $metadata['magnitude'] ?? null, 'depth_km' => $metadata['depth_km'] ?? null,
                'priority' => $signal->priority, 'event_time' => $metadata['event_time'] ?? null, 'title' => $signal->title,
            ] : [
                'id' => $signal->id, 'layer' => $layer, 'geometry' => $metadata['geometry'] ?? null,
                'priority' => $signal->priority, 'severity' => $metadata['severity'] ?? null,
                'effective' => $metadata['effective'] ?? null, 'expires' => $metadata['expires'] ?? null, 'headline' => $signal->title,
            ];
        })->filter()->values()]);
    }

    private function query(BmkgSignalIndexRequest $request, ?string $layer): Builder
    {
        $query = Signal::query()->with(['sources', 'events'])->whereHas('sources', fn (Builder $q) => $q->where('provider', 'BMKG'));
        $magnitude = $this->jsonText('magnitude');
        $severity = $this->jsonText('severity');
        if ($layer === 'earthquake') $query->whereRaw($magnitude.' IS NOT NULL');
        if ($layer === 'weather-alert') $query->whereRaw($severity.' IS NOT NULL');
        if ($request->filled('priority')) $query->where('priority', strtoupper((string) $request->query('priority')));
        foreach (['severity', 'urgency', 'certainty'] as $field) if ($request->filled($field)) $query->whereRaw($this->jsonText($field).' = ?', [strtolower((string) $request->query($field))]);
        if ($request->filled('min_magnitude')) $query->whereRaw('CAST('.$magnitude.' AS DECIMAL) >= ?', [$request->float('min_magnitude')]);
        if ($request->filled('max_magnitude')) $query->whereRaw('CAST('.$magnitude.' AS DECIMAL) <= ?', [$request->float('max_magnitude')]);
        if ($request->boolean('felt')) $query->whereRaw($this->jsonText('felt_description').' IS NOT NULL');
        if ($request->filled('from')) $query->where('last_updated_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->where('last_updated_at', '<=', $request->date('to')->endOfDay());
        if ($request->boolean('active')) {
            $expires = $this->jsonText('expires');
            if (DB::connection()->getDriverName() === 'pgsql') {
                $query->whereRaw($expires.' IS NULL OR ('.$expires.')::timestamptz >= NOW()');
            } else {
                $query->whereRaw($expires.' IS NULL OR datetime('.$expires.') >= datetime(?)', [now()->toISOString()]);
            }
        }
        if ($request->filled('bbox')) {
            [$minLon, $minLat, $maxLon, $maxLat] = array_map('floatval', explode(',', (string) $request->query('bbox')));
            $query->where(function (Builder $bboxQuery) use ($minLon, $maxLon, $minLat, $maxLat): void {
                $bboxQuery->where(function (Builder $point) use ($minLon, $maxLon, $minLat, $maxLat): void {
                    $point->whereBetween('longitude', [$minLon, $maxLon])->whereBetween('latitude', [$minLat, $maxLat]);
                })->orWhereNull('longitude')->orWhereNull('latitude');
            });
        }
        return $query->latest('last_updated_at');
    }

    private function jsonText(string $field): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "metadata->>'{$field}'"
            : "json_extract(metadata, '$.{$field}')";
    }

    private function isType(Signal $signal, string $layer): bool
    {
        return $this->layer($signal->metadata ?? []) === $layer && $signal->sources()->where('provider', 'BMKG')->exists();
    }

    private function layer(array $metadata): ?string
    {
        if (isset($metadata['magnitude'])) return 'earthquake';
        if (isset($metadata['severity'])) return 'weather-alert';
        return null;
    }
}