export type Signal = {
  id: number;
  title: string;
  summary: string | null;
  type: string;
  category: string | null;
  priority: string;
  first_detected_at: string;
  last_updated_at: string;
  confidence_score: number;
  importance_score: number;
  source_count: number;
};

export type BmkgSignal = Signal & {
  signal_type: string;
  event_time: string | null;
  latitude: number | null;
  longitude: number | null;
  magnitude: number | null;
  depth_km: number | null;
  region: string | null;
  felt: boolean;
  felt_description: string | null;
  potential: string | null;
  tsunami_status: string | null;
  headline: string;
  severity: string | null;
  urgency: string | null;
  certainty: string | null;
  area_description: string | null;
  effective: string | null;
  expires: string | null;
  geometry: unknown;
  source: { provider: string; attribution: string | null; url: string | null };
  timeline?: { type: string; summary: string; occurred_at: string }[];
};

type Paginated<T> = { data: T[]; meta: { current_page: number; last_page: number; total: number } };

const apiBaseUrl = process.env.NEXT_PUBLIC_RADAR_API_URL ?? "http://127.0.0.1:8000/api/v1";

async function request<T>(path: string, signal?: AbortSignal): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, { signal, cache: "no-store" });
  if (!response.ok) throw new Error(`RADAR_API_${response.status}`);
  return response.json() as Promise<T>;
}

export function getSignals(signal?: AbortSignal): Promise<Paginated<Signal>> {
  return request<Paginated<Signal>>("/signals", signal);
}

export function getEarthquakes(signal?: AbortSignal): Promise<Paginated<BmkgSignal>> {
  return request<Paginated<BmkgSignal>>("/earthquakes", signal);
}

export function getWeatherAlerts(signal?: AbortSignal): Promise<Paginated<BmkgSignal>> {
  return request<Paginated<BmkgSignal>>("/weather/alerts?active=true", signal);
}

export function getMapSignals(signal?: AbortSignal): Promise<{ data: MapSignal[] }> {
  return request<{ data: MapSignal[] }>("/signals/map?bbox=95,-11,141,6&layers=earthquake,weather-alert", signal);
}

export type MapSignal = {
  id: number;
  layer: "earthquake" | "weather-alert";
  lat?: number;
  lng?: number;
  magnitude?: number | null;
  depth_km?: number | null;
  priority: string;
  event_time?: string | null;
  title?: string;
  geometry?: unknown;
  headline?: string;
  severity?: string | null;
  effective?: string | null;
  expires?: string | null;
};

export type Location = { id: number; name: string; type: string | null; level: string; code: string; forecast_ready: boolean; province: string | null; regency: string | null; district: string | null; breadcrumbs?: string[]; timezone: string | null };
export type ForecastPoint = { utc_datetime: string; local_datetime: string | null; temperature_c: number | null; humidity_percent: number | null; weather_description: string | null; weather_description_en: string | null; wind_speed_kmh: number | null; wind_direction: string | null; cloud_cover_percent: number | null; visibility: string | null; analysis_date: string | null };
export type ForecastResponse = { location: { adm4: string; name: string | null; province: string | null; regency: string | null; latitude: number | null; longitude: number | null; timezone: string | null }; provider: string; attribution: string; analysis_date: string | null; forecast: ForecastPoint[]; cached?: boolean; cache_expires_at?: string };

export function searchLocations(query: string, signal?: AbortSignal): Promise<Paginated<Location>> { return request<Paginated<Location>>(`/locations?q=${encodeURIComponent(query)}&per_page=20`, signal); }
export function getLocationForecast(id: number, signal?: AbortSignal): Promise<ForecastResponse> { return request<ForecastResponse>(`/locations/${id}/forecast`, signal); }