export function csrfToken() {
  const token = document.head.querySelector('meta[name="csrf-token"]')?.content;
  if (!token) throw new Error("Missing CSRF token");
  return token;
}

let plotApiAvailable = true;

export async function fetchPlot(body) {
  if (!plotApiAvailable) {
    const error = new Error(
      "El servicio de gráficos no está disponible en este entorno."
    );
    error.status = 503;
    error.payload = { message: error.message };
    error.isPlotError = true;
    throw error;
  }
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
    //if (r.status === 404 || r.status === 503) {
    //  plotApiAvailable = false;
    //}
    if (r.status === 503) {
      plotApiAvailable = false;
    }   
    let payload;
    try {
      payload = await r.json();
    } catch (_) {
      payload = await r.text();
    }
    const error = new Error("Plot API request failed");
    error.status = r.status;
    error.payload = payload;
    error.isPlotError = true;
    throw error;
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

  /* 4. Replace categorical IDs in bar axes so charts show labels, not numeric IDs */
  const mapValue = (v) =>
    Object.prototype.hasOwnProperty.call(flat, v) ? flat[v] : v;

  figure.data.forEach((trace) => {
    if (trace.type !== "bar") return;
    const orientation = trace.orientation || "v";
    if (orientation === "h" && Array.isArray(trace.y)) {
      trace.y = trace.y.map(mapValue);
    } else if (Array.isArray(trace.x)) {
      trace.x = trace.x.map(mapValue);
    }
    // Nota: se elimina la rotación automática de etiquetas en barras verticales.
  });
}

export function setupAdvancedFilters(form, options = {}) {
  if (!form) return;
  const toggleSelector = options.toggleSelector || "[data-advanced-toggle]";
  const containerSelector = options.containerSelector || "[data-advanced-container]";
  const toggle = form.querySelector(toggleSelector);
  const container = form.querySelector(containerSelector);
  if (!toggle || !container) return;

  const openLabel = toggle.dataset.openLabel || toggle.textContent.trim() || "Filtros Avanzados";
  const closeLabel = toggle.dataset.closeLabel || "Ocultar filtros";

  const sync = () => {
    const isVisible = container.classList.contains("is-visible");
    toggle.textContent = isVisible ? closeLabel : openLabel;
    toggle.setAttribute("aria-expanded", isVisible ? "true" : "false");
  };

  sync();

  toggle.addEventListener("click", () => {
    container.classList.toggle("is-visible");
    sync();
  });
}

export function attachNoticeTarget(form, selector = "[data-notice]") {
  if (!form) {
    return { show: () => {}, clear: () => {} };
  }
  const el = form.querySelector(selector);
  if (!el) {
    return { show: () => {}, clear: () => {} };
  }

  const show = (message, type = "info") => {
    if (!message) {
      el.textContent = "";
      el.classList.remove("is-visible", "notice--info", "notice--error", "notice--success");
      return;
    }
    el.textContent = message;
    el.classList.add("is-visible");
    el.classList.remove("notice--info", "notice--error", "notice--success");
    el.classList.add(`notice--${type}`);
  };

  const clear = () => show("");

  return { show, clear };
}

export function normalisePlotError(err) {
  if (!err) {
    return {
      message: "Ocurrió un error desconocido.",
      severity: "error",
    };
  }

  if (err.isPlotError) {
    if (err.status === 400 || err.status === 404 || err.status === 204) {
      const payloadMessage =
        typeof err.payload === "string"
          ? err.payload
          : err.payload?.message || err.payload?.detail;
      return {
        message:
          payloadMessage ||
          "No se encontraron datos para los filtros seleccionados. Ajusta el rango o los parámetros.",
        severity: "info",
      };
    }
    const payloadMessage =
      typeof err.payload === "string"
        ? err.payload
        : err.payload?.message || err.payload?.detail;
    return {
      message:
        payloadMessage ||
        "No se pudo obtener información del servicio. Intenta nuevamente.",
      severity: "error",
    };
  }

  if (err.message) {
    return { message: err.message, severity: "error" };
  }

  return { message: String(err), severity: "error" };
}

export function plotIsEmpty(figure) {
  if (!figure || !Array.isArray(figure.data) || figure.data.length === 0) {
    return true;
  }

  return figure.data.every((trace = {}) => {
    const vectors = [trace.y, trace.x, trace.values];
    if (vectors.some((arr) => Array.isArray(arr) && arr.length > 0)) {
      return false;
    }

    const z = trace.z;
    if (Array.isArray(z)) {
      const flattened = typeof z.flat === "function" ? z.flat() : [].concat(...z);
      if (flattened.length > 0) return false;
    } else if (typeof z === "number") {
      return false;
    }

    return true;
  });
}
