import { getEarthquakes, BmkgSignal } from "@/lib/api";

export default async function EarthquakesPage() {
  let data: BmkgSignal[] = [];
  let error = false;
  try { data = (await getEarthquakes()).data; } catch { error = true; }
  return <main style={{ padding: 40, maxWidth: 1100, margin: "auto" }}>
    <p className="eyebrow">BMKG / EARTHQUAKE INTELLIGENCE</p>
    <h1 style={{ margin: "16px 0" }}>Earthquakes</h1>
    <p style={{ color: "var(--muted)", marginBottom: 28 }}>Official BMKG earthquake observations transformed into RADAR Signals.</p>
    {error ? <p>Earthquake API unavailable.</p> : data.length === 0 ? <p>No persisted BMKG earthquake Signals yet.</p> : <div style={{ display: "grid", gap: 12 }}>{data.map((item) => <article key={item.id} style={{ border: "1px solid var(--line)", padding: 20, background: "var(--panel)" }}><strong>M{item.magnitude ?? "—"} · {item.region ?? "Indonesia"}</strong><p>{item.event_time} · depth {item.depth_km ?? "—"} km · priority {item.priority}</p><small>{item.source.attribution ?? "BMKG"}</small></article>)}</div>}
  </main>;
}