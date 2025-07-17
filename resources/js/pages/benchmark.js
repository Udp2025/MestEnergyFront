import Plotly from "plotly.js-dist-min";
import { fetchPlot, applyMapping } from "../plot.js";

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
    style: { color: "site_id", marker_size: 10 },
  },
};

let first = true;
async function render() {
  const { figure, config, mapping } = await fetchPlot(FIXED_BODY);
  applyMapping(figure, mapping);
  const el = document.getElementById("energyChart");
  if (first) {
    await Plotly.newPlot(el, figure.data, figure.layout, config);
    first = false;
  } else {
    await Plotly.react(el, figure.data, figure.layout, config);
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
