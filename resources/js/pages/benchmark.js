import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";
import debounce from "lodash.debounce";

const TODAY = new Date().toISOString().slice(0, 10);
const DEFAULTS = {
  metric: "power_w",
  from: TODAY,
  to: TODAY,
  period: "H",
  agg: "avg",
};

/* ------------------------------------------------------------------ */
/*  Everything lives inside DOMContentLoaded                          */
/* ------------------------------------------------------------------ */
document.addEventListener("DOMContentLoaded", () => {
  /* -- safe DOM look-ups ------------------------------------------- */
  const form = document.getElementById("plot-filters");
  const canvas = document.getElementById("energyChart");
  if (!form || !canvas) {
    console.error("benchmark.js: required DOM nodes not found");
    return; // bail early, avoid further errors
  }

  /* -- helpers ----------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];

  function buildRequest() {
    const metric = v("metric");
    const period = v("period");
    const func = v("agg");
    const from = v("from");
    const to = v("to");

    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
      },
      aggregation: [
        {
          group_by: ["site_id", "device_id"],
          aggregations: { [metric]: [func] },
          time_window: period,
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "line",
        x: "measurement_time",
        y: `${metric}_${func}`,
        style: { color: "device_id" },
      },
    };
  }

  /* -- draw / redraw ---------------------------------------------- */
  let first = true;
  async function draw() {
    try {
      const body = buildRequest();
      const { figure, config, mapping } = await fetchPlot(body);
      applyMapping(figure, mapping);

      if (first) {
        await Plotly.newPlot(canvas, figure.data, figure.layout, config);
        first = false;
      } else {
        await Plotly.react(canvas, figure.data, figure.layout, config);
      }
    } catch (err) {
      console.error(err);
      alert("No se pudo cargar el gráfico: " + err.message);
    }
  }

  /* -- event wiring ----------------------------------------------- */
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    draw();
  });
  form.addEventListener("input", debounce(draw, 400));

  draw(); // initial render
});
