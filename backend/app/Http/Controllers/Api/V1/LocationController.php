<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Radar\Forecast\ForecastService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::query()->where('is_active', true)->where('status', 'ACTIVE');
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $normalized = mb_strtolower(preg_replace('/[^\\pL\\pN ]/u', '', preg_replace('/\\s+/u', ' ', $term)) ?? '');
            $query->where(fn ($q) => $q->where('normalized_name', 'like', '%'.$normalized.'%')->orWhere('code', 'like', $term.'%'))
                ->orderByRaw('CASE WHEN code = ? OR REPLACE(code, \'-\', \'\') = ? THEN 0 WHEN normalized_name = ? THEN 1 WHEN normalized_name LIKE ? THEN 2 ELSE 3 END', [$term, preg_replace('/\\D/', '', $term), $normalized, $normalized.'%']);
        }
        if ($request->filled('level')) $query->where('level', strtoupper((string) $request->query('level')));
        foreach (['code' => 'code'] as $input => $column) if ($request->filled($input)) $query->where($column, 'like', (string) $request->query($input).'%');
        if ($request->boolean('forecast_ready')) $query->where('level', 'KELURAHAN')->whereNotNull('adm4_code');
        if ($request->filled('province')) $query->where('adm1_code', 'like', (string) $request->query('province').'%');
        if ($request->filled('regency')) $query->where('adm2_code', 'like', (string) $request->query('regency').'%');
        if ($request->filled('district')) $query->where('adm3_code', 'like', (string) $request->query('district').'%');
        return LocationResource::collection($query->orderBy('name')->paginate(min(max($request->integer('per_page', 20), 1), 50)));
    }

    public function show(Location $location): LocationResource { return new LocationResource($location); }

    public function forecast(Location $location, ForecastService $service)
    {
        abort_unless($location->forecast_ready, 422, 'LOCATION_NOT_FORECAST_READY');
        return response()->json($service->get($location));
    }
}