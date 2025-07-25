export async function fetchPlot(body) {
  const API_BASE = import.meta.env.VITE_PLOT_API_BASE;
  const API_KEY = import.meta.env.VITE_PLOT_API_KEY;

  if (!API_BASE || !API_KEY) throw new Error("Missing API configuration");

  const r = await fetch(`${API_BASE}/items/data/plot`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "x-api-key": API_KEY,
    },
    body: JSON.stringify(body),
  });
  if (!r.ok) throw new Error(`API ${r.status}: ${await r.text()}`);
  return r.json();
}

export function applyMapping(figure, mapping = {}) {
  /* 1. Flatten nested maps ➜ {old → new} */
  const flat = {};
  Object.entries(mapping).forEach(([_, inner]) => {
    if (inner && typeof inner === "object" && !Array.isArray(inner)) {
      Object.assign(flat, inner);
    } else if (inner !== undefined) {
      flat[_] = inner;
    }
  });
  if (Object.keys(flat).length === 0) return;

  /* 2. Prepare helpers */
  const entries = Object.entries(flat).sort((a, b) => b[0].length - a[0].length);
  const esc = (s) => String(s).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

  /* 3. Walk every trace */
  figure.data.forEach((trace) => {
    if (trace.showlegend === false) return; // skip hidden

    /* -- coerce so .replace exists -------------------------- */
    let label = String(trace.name ?? ""); // '' if undefined
    let hover = typeof trace.hovertemplate === "string" ? trace.hovertemplate : null;

    /* -- replace every key ---------------------------------- */
    entries.forEach(([oldVal, newVal]) => {
      // console.log("Legend Variables: ", oldVal, newVal);
      if (newVal === undefined) return; // no mapping
      const re = new RegExp(`\\b${esc(oldVal)}\\b`, "g");
      label = label.replace(re, newVal);
      if (hover) hover = hover.replace(re, newVal);
    });

    /* -- write back ----------------------------------------- */
    trace.name = label;
    trace.legendgroup = label; // keep toggling tidy
    if (hover) trace.hovertemplate = hover;
  });
}
