"use client";

import { useEffect, useRef, useState } from "react";
import { getLocationForecast, searchLocations, Location, ForecastResponse } from "@/lib/api";

export default function WeatherPage() {
  const [query, setQuery] = useState("");
  const [locations, setLocations] = useState<Location[]>([]);
  const [forecast, setForecast] = useState<ForecastResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (query.trim().length < 2) return;
    const timer = window.setTimeout(async () => {
      abortRef.current?.abort();
      const controller = new AbortController();
      abortRef.current = controller;
      setLoading(true); setError(false);
      try { setLocations((await searchLocations(query, controller.signal)).data); }
      catch (cause) { if (!(cause instanceof DOMException && cause.name === "AbortError")) setError(true); }
      finally { if (!controller.signal.aborted) setLoading(false); }
    }, 300);
    return () => window.clearTimeout(timer);
  }, [query]);

  async function selectLocation(location: Location) {
    if (!location.forecast_ready) return;
    setLoading(true); setError(false);
    try { setForecast(await getLocationForecast(location.id)); } catch { setError(true); } finally { setLoading(false); }
  }

  return <main style={{ padding: 40, maxWidth: 1100, margin: "auto" }}>
    <p className="eyebrow">BMKG / LOCATION INTELLIGENCE</p><h1 style={{ margin: "16px 0" }}>Weather forecast</h1>
    {!forecast ? <><p style={{ color: "var(--muted)", marginBottom: 20 }}>Search the RADAR registry. Forecasts are on demand for compatible ADM4 locations.</p><label htmlFor="location-search">Location search</label><input id="location-search" value={query} onChange={(event) => { setQuery(event.target.value); if (event.target.value.trim().length < 2) setLocations([]); }} placeholder="Village, code, or hierarchy" autoComplete="off" style={{ display: "block", width: "100%", padding: 12, margin: "8px 0 16px" }} />{loading && <p role="status">Loading…</p>}{error && <p role="alert">Location API unavailable. Try again.</p>}{!loading && query.trim().length >= 2 && locations.length === 0 && !error && <p>No matching locations.</p>}<div role="listbox" aria-label="Location results" style={{ display: "grid", gap: 10 }}>{locations.map((location) => <button type="button" role="option" aria-selected="false" key={location.id} onClick={() => void selectLocation(location)} disabled={!location.forecast_ready} style={{ textAlign: "left", border: "1px solid var(--line)", padding: 16, background: "var(--panel)" }}><strong>{location.name}</strong><br /><small>{location.breadcrumbs?.join(" · ") || [location.province, location.regency, location.district].filter(Boolean).join(" · ")} · {location.code}{location.forecast_ready ? " · forecast ready" : " · no ADM4 forecast"}</small></button>)}</div></> : <><button type="button" onClick={() => setForecast(null)}>← Search another location</button><p style={{ color: "var(--muted)" }}>{forecast.location.name} · {forecast.location.timezone} · {forecast.cached ? "cached" : "fresh"}</p><p style={{ color: "var(--muted)", margin: "10px 0 24px" }}>{forecast.attribution}</p><div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(180px,1fr))", gap: 10 }}>{forecast.forecast.map((point) => <article key={point.utc_datetime} style={{ border: "1px solid var(--line)", padding: 16, background: "var(--panel)" }}><small>{point.local_datetime}</small><h2>{point.temperature_c}°C</h2><p>{point.weather_description}</p><small>Humidity {point.humidity_percent}% · Wind {point.wind_speed_kmh} km/h</small></article>)}</div></>}
  </main>;
}