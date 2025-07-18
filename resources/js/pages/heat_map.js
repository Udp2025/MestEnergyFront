/* resources/js/pages/heat_map.js */
import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";
import debounce from "lodash.debounce";

const TODAY = new Date().toISOString().slice(0, 10);

/* ---------------- DOM refs & helpers ----------------------------- */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("plot-filters");
  const chart = document.getElementById("heatChart");
  const runBtn = document.getElementById("run");
  if (!form || !chart) {
    console.error("heat_map: DOM missing");
    return;
  }

  const v = (n) => form[n]?.value?.trim();

  /* --- axis → allowed span rules --------------------------------- */
  // key = 'x|y'  value = {maxDays, timeWindow}
  const RULES = {
    "hour|weekday": { days: 7, tw: "H" },
    "weekday|hour": { days: 7, tw: "H" },
    "hour|day": { days: 1, tw: "H" },
    "day|hour": { days: 1, tw: "H" },
    "weekday|day": { days: 7, tw: "D" },
    "day|weekday": { days: 7, tw: "D" },
    // default → no restriction
  };

  function enforceDateWindow() {
    const key = `${v("x")}|${v("y")}`;
    const rule = RULES[key];
    if (!rule) {
      // unrestricted
      form.from.disabled = form.to.disabled = false;
      runBtn.disabled = false;
      return;
    }
    /* calculate allowed range */
    const from = new Date(form.from.value || TODAY);
    const toMax = new Date(from);
    toMax.setDate(from.getDate() + rule.days - 1);
    form.to.min = form.from.value;
    form.to.max = toMax.toISOString().slice(0, 10);
    if (new Date(form.to.value) > toMax) form.to.value = form.to.max;
    runBtn.disabled = false;
  }

  /* --- build safe backend body ----------------------------------- */
  function buildRequest() {
    const rule = RULES[`${v("x")}|${v("y")}`] || {};
    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${v("from")} 00:00:00, ${v("to")} 23:59:59]`,
      },
      aggregation: [
        {
          group_by: [v("x"), v("y")],
          aggregations: { [v("z")]: ["avg"] },
          time_window: rule.tw || "D",
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "heatmap",
        x: v("x"),
        y: v("y"),
        z: `${v("z")}_avg`,
      },
    };
  }

  /* --- Render ----------------------------------------------------- */
  let first = true;
  async function draw() {
    try {
      const body = buildRequest();
      const { figure, config, mapping } = await fetchPlot(body);
      applyMapping(figure, mapping);
      if (first) {
        await Plotly.newPlot(chart, figure.data, figure.layout, config);
        first = false;
      } else {
        await Plotly.react(chart, figure.data, figure.layout, config);
      }
    } catch (e) {
      alert("Error: " + e.message);
      console.error(e);
    }
  }

  /* --- Events ----------------------------------------------------- */
  form.addEventListener(
    "input",
    debounce(() => {
      enforceDateWindow();
    }, 200)
  );
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    draw();
  });

  enforceDateWindow(); // initialise
});
