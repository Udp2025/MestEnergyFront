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
  const $ = (id) => document.getElementById(id);
  const runBtn = $("run");
  const form = $("plot-filters");
  const chart = $("lineChart");
  if (!form || !chart) {
    console.error("timeseries.js: required DOM nodes not found");
    return; // bail early, avoid further errors
  }

  /* -- helpers ----------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];
  function buildBody() {
    const metric = v("metric");
    const period = v("period");
    const func = v("agg");
    const from = v("from");
    const to = v("to");

    console.log("fechas: ", `[${from} 00:00:00, ${to} 23:59:59]`);

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

  /* ------------- Event wiring ----------------------------------- */
  form.onsubmit = async (e) => {
    e.preventDefault();
    if (runBtn.disabled) return;
    // basic date guard
    const from = v("from");
    const to = v("to");
    if (from > to) {
      alert("Rango de fechas inválido: 'Desde' es mayor que 'Hasta'.");
      return;
    }
    runBtn.disabled = true;
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      await Plotly.react(chart, figure.data, figure.layout, config);
    } catch (err) {
      console.error(err);
      alert("No se pudo cargar el gráfico: " + (err?.message || err));
    } finally {
      runBtn.disabled = false;
    }
  };
});
