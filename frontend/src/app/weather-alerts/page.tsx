import { getWeatherAlerts, BmkgSignal } from "@/lib/api";

export default async function WeatherAlertsPage() {
  let data: BmkgSignal[] = [];
  let error = false;
  try { data = (await getWeatherAlerts()).data; } catch { error = true; }
  return <main style={{ padding: 40, maxWidth: 1100, margin: "auto" }}>
    <p className="eyebrow">BMKG / WEATHER ALERTS</p>
    <h1 style={{ margin: "16px 0" }}>Weather Alerts</h1>
    <p style={{ color: "var(--muted)", marginBottom: 28 }}>Active official BMKG nowcast warnings with CAP provenance.</p>
    {error ? <p>Weather alert API unavailable.</p> : data.length === 0 ? <p>No active persisted BMKG weather warnings.</p> : <div style={{ display: "grid", gap: 12 }}>{data.map((item) => <article key={item.id} style={{ border: "1px solid var(--line)", padding: 20, background: "var(--panel)" }}><strong>{item.headline}</strong><p>{item.area_description ?? "Area not specified"}</p><p>{item.severity} · {item.urgency} · {item.certainty}</p><small>{item.source.attribution ?? "BMKG"}</small></article>)}</div>}
  </main>;
}