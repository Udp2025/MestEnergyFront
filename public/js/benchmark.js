// New Chart Rendering Logic -----------------------------------------------

/*
 * Single fixed request body for testing
 * (voltages, dates, aggregation etc. are hard-coded)
 */

const FIXED_BODY_HEAT = {
  table: "measurements",
  filter_map: {
    measurement_time: ">=2025-07-06 00:00:00",
  },
  aggregation: [
    {
      time_column: "measurement_time",
      time_window: "H",
      aggregations: { energy_wh: ["avg"] },
    },
  ],
  chart: {
    chart_type: "heatmap",
    x: "hour",
    y: "weekday",
    z: "energy_wh_avg",
  },
};

const FIXED_BODY = {
  table: "measurements",
  filter_map: {
    measurement_time: ">=2025-07-06 00:00:00",
  },
  aggregation: [
    {
      group_by: ["site_id", "device_id"],
      time_column: "measurement_time",
      time_window: "6H",
      aggregations: { energy_wh: ["avg"] },
    },
  ],
  chart: {
    chart_type: "line",
    x: "measurement_time",
    y: "energy_wh_avg",
    style: { color: "device_id", marker_size: 10 },
  },
};

/* tiny helper */
async function fetchPlot(body) {
  const { API_BASE, API_KEY } = window.APP_CONF;

  const r = await fetch(`${API_BASE}/items/data/plot  `, {
    method: "POST",
    //mode: "cors",
    headers: {
      "Content-Type": "application/json",
      "x-api-key": `${API_KEY}`,
    },
    body: JSON.stringify(body),
  });

  if (!r.ok) throw new Error(`API ${r.status}: ${await r.text()}`);
  return r.json(); // large JSON is fine at dev scale
}

let first = true;
function applyMapping(figure, mapping = {}) {
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
      console.log(oldVal, newVal);
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

async function render() {
  try {
    const { figure, config, mapping = {} } = await fetchPlot(FIXED_BODY);
    applyMapping(figure, mapping); // ← now handles many maps at once

    const chart = document.getElementById("energyChart");
    if (first) {
      await Plotly.newPlot(chart, figure.data, figure.layout, config);
      first = false;
    } else {
      await Plotly.react(chart, figure.data, figure.layout, config);
    }
  } catch (err) {
    console.error(err);
    alert("Could not load chart: " + err.message);
  }
}

/* --- lazy-load: only fire when visible ------------------------------ */
const io = new IntersectionObserver(([e]) => {
  if (e.isIntersecting) {
    render();
    io.disconnect();
  }
});
io.observe(document.getElementById("energyChart"));
