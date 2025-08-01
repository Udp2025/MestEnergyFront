// New Chart Rendering Logic -----------------------------------------------

/*
 * Single fixed request body for testing
 * (voltages, dates, aggregation etc. are hard-coded)
 */
const FIXED_BODY = {
  table: "test_telemetry_data",
  filter_map: {
    voltage: ">125",
    measurement_time: "[2020-01-01 00:00:00, 2021-12-31 23:59:59]",
  },
  aggregation: [
    {
      group_by: ["site_id"],
      time_column: "measurement_time",
      time_window: "M",
      aggregations: { power: ["avg"] },
    },
  ],
  chart: {
    chart_type: "line",
    x: "measurement_time",
    y: "power_avg",
    style: { color: "site_id", line_dash: "site_id" },
  },
};

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
