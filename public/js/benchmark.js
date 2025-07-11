// New Chart Rendering Logic -----------------------------------------------

/*
 * Single fixed request body for testing
 * (voltages, dates, aggregation etc. are hard-coded)
 */
const FIXED_BODY = {
  table: "measurements",
  filter_map: {
    measurement_time: ">=2025-07-06 00:00:00",
  },
  aggregation: [
    {
      group_by: ["device_id"],
      time_column: "measurement_time",
      time_window: "H",
      aggregations: { power_w: ["avg"] },
    },
  ],
  chart: {
    chart_type: "line",
    x: "measurement_time",
    y: "power_w_avg",
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

/* first paint  */
let first = true;
async function render() {
  try {
    const { figure, config } = await fetchPlot(FIXED_BODY);
    const chart = document.getElementById("energyChart");

    console.log("Figure JSON Plotly: ", figure);
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
