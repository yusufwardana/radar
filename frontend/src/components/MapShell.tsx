"use client";

import { useEffect, useRef } from "react";
import maplibregl from "maplibre-gl";
import { getMapSignals, MapSignal } from "@/lib/api";
import "maplibre-gl/dist/maplibre-gl.css";

export function MapShell() {
  const container = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!container.current) return;
    const map = new maplibregl.Map({
      container: container.current,
      style: { version: 8, sources: {}, layers: [{ id: "background", type: "background", paint: { "background-color": "#101619" } }] },
      center: [110.37, -7.8],
      zoom: 4.5,
      attributionControl: false,
    });
    getMapSignals().then(({ data }) => {
      const earthquakes = data.filter((item) => item.layer === "earthquake");
      const warnings = data.filter((item) => item.layer === "weather-alert");
      const render = () => { addEarthquakes(map, earthquakes); addWeatherWarnings(map, warnings); };
      if (!map.isStyleLoaded()) map.once("load", render); else render();
    }).catch(() => undefined);
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), "top-right");
    return () => map.remove();
  }, []);

  return <div ref={container} aria-label="RADAR map surface" style={{ height: "150px", width: "100%" }} />;
}

function addEarthquakes(map: maplibregl.Map, signals: MapSignal[]) {
  map.addSource("radar-earthquakes", { type: "geojson", data: { type: "FeatureCollection", features: signals.flatMap((signal) => signal.lat !== undefined && signal.lng !== undefined ? [{ type: "Feature" as const, geometry: { type: "Point" as const, coordinates: [signal.lng, signal.lat] as [number, number] }, properties: { title: signal.title, magnitude: signal.magnitude } }] : []) } });
  map.addLayer({ id: "radar-earthquake-points", type: "circle", source: "radar-earthquakes", paint: { "circle-radius": ["interpolate", ["linear"], ["coalesce", ["get", "magnitude"], 3], 3, 5, 6, 11], "circle-color": "#c8f36a", "circle-stroke-color": "#0b0d0f", "circle-stroke-width": 1 } });
}

function addWeatherWarnings(map: maplibregl.Map, signals: MapSignal[]) {
  const features = signals.flatMap((signal) => signal.geometry ? [{ type: "Feature" as const, geometry: signal.geometry as GeoJSON.Geometry, properties: { headline: signal.headline, severity: signal.severity, effective: signal.effective, expires: signal.expires } }] : []);
  map.addSource("radar-weather-alerts", { type: "geojson", data: { type: "FeatureCollection", features } });
  map.addLayer({ id: "radar-weather-alert-polygons", type: "fill", source: "radar-weather-alerts", paint: { "fill-color": "#ffb86b", "fill-opacity": 0.22, "fill-outline-color": "#ffb86b" } });
  map.on("click", "radar-weather-alert-polygons", (event) => {
    const feature = event.features?.[0];
    if (!feature) return;
    new maplibregl.Popup().setLngLat(event.lngLat).setHTML(`<strong>${feature.properties?.headline ?? "BMKG Weather Alert"}</strong><br/>${feature.properties?.severity ?? ""}<br/>${feature.properties?.effective ?? ""} — ${feature.properties?.expires ?? ""}`).addTo(map);
  });
}