export function csrfToken() {
  const token = document.head.querySelector('meta[name="csrf-token"]')?.content;
  if (!token) throw new Error("Missing CSRF token");
  return token;
}

export async function fetchPlot(body) {
  const r = await fetch("/charts/plot", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      Accept: "application/json",
    },
    body: JSON.stringify(body),
  });
  if (!r.ok) {
    let reason = await r.text();
    try {
      reason = JSON.parse(reason);
    } catch (_) {
      // keep plain text
    }
    throw new Error(`API ${r.status}: ${JSON.stringify(reason)}`);
  }
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
  const entries = Object.entries(flat).sort(
    (a, b) => b[0].length - a[0].length
  );
  const esc = (s) => String(s).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

  /* 3. Walk every trace */
  figure.data.forEach((trace) => {
    if (trace.showlegend === false) return; // skip hidden

    /* -- coerce so .replace exists -------------------------- */
    let label = String(trace.name ?? ""); // '' if undefined
    let hover =
      typeof trace.hovertemplate === "string" ? trace.hovertemplate : null;

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
