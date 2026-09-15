import { MapShell } from "@/components/MapShell";
import { getSignals, Signal } from "@/lib/api";
import styles from "./page.module.css";

export default async function Home() {
  let signals: Signal[] = [];
  let apiUnavailable = false;
  try { signals = (await getSignals()).data; } catch { apiUnavailable = true; }

  return (
    <main className={styles.shell}>
      <aside className={styles.sidebar}>
        <div className={styles.brand}><span className={styles.brandMark}>R</span><span>RADAR</span></div>
        <p className={styles.kicker}>PUBLIC INTELLIGENCE</p>
        <nav className={styles.nav} aria-label="Primary navigation">
          <a className={styles.active} href="#signals">Signals <span>01</span></a>
          <a href="#map">Map <span>02</span></a>
          <a href="#sources">Sources <span>03</span></a>
          <a href="/earthquakes">Earthquakes <span>05</span></a>
          <a href="/weather-alerts">Weather alerts <span>06</span></a>
          <a href="#system">System <span>04</span></a>
        </nav>
        <div className={styles.sidebarFooter}><span className={styles.statusDot} /> API boundary only<br /><small>No direct provider access</small></div>
      </aside>
      <section className={styles.content}>
        <header className={styles.header}>
          <div><p className={styles.eyebrow}>LIVE PUBLIC INTELLIGENCE</p><h1>What changed?</h1><p className={styles.subhead}>Actionable observations, preserved from their original public sources.</p></div>
          <div className={styles.live}><span className={styles.statusDot} /> REST / READY</div>
        </header>
        <section className={styles.signalSection} id="signals">
          <div className={styles.sectionHeading}><div><p className={styles.eyebrow}>SIGNAL FEED</p><h2>Attention queue</h2></div><span className={styles.count}>{signals.length.toString().padStart(2, "0")} observed</span></div>
          {apiUnavailable ? <div className={styles.empty}><strong>Backend unavailable</strong><p>RADAR is waiting for the Laravel API at <code>{process.env.NEXT_PUBLIC_RADAR_API_URL ?? "http://127.0.0.1:8000/api/v1"}</code>.</p></div> : signals.length === 0 ? <div className={styles.empty}><strong>No Signals yet</strong><p>Connect a verified source and run ingestion before intelligence appears here.</p></div> : <div className={styles.feed}>{signals.map((signal) => <article className={styles.card} key={signal.id}><div className={styles.priority}>{signal.priority}</div><div><h3>{signal.title}</h3><p>{signal.summary ?? "No summary available."}</p><small>{signal.source_count} source{signal.source_count === 1 ? "" : "s"} · confidence {signal.confidence_score}</small></div><time>{new Date(signal.last_updated_at).toLocaleString()}</time></article>)}</div>}
        </section>
        <section className={styles.grid} id="map"><div className={styles.panel}><p className={styles.eyebrow}>MAP INTELLIGENCE</p><h2>Viewport-ready</h2><p>MapLibre is reserved for bounded RADAR API queries. Provider layers remain disabled until verified backend sources exist.</p><div className={styles.mapPlaceholder}><MapShell /></div></div><div className={styles.panel} id="sources"><p className={styles.eyebrow}>SOURCE STATUS</p><h2>Provenance first</h2><div className={styles.sourceRow}><span className={styles.statusDot} /> No sources registered <span className={styles.muted}>—</span></div><p className={styles.note}>Source health and attribution will appear here after registry records are configured.</p></div></section>
      </section>
    </main>
  );
}