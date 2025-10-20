/* resources/js/utils/plot/core.js
 * One simple helper for every “/items/data” call
 * ---------------------------------------------------------------
 * usage:
 *   import { fetchDB } from "../utils/plot/core";
 *
 *   const sites   = await fetchJson({ table: "sites" });
 *   const devices = await fetchJson({
 *       table: "devices",
 *       filter_map: { site_id: 42 },
 *       select_columns: ["device_id", "device_name"]
 *   });
 */

import { csrfToken } from "./plot";

let dbApiAvailable = true;

export async function fetchDB(body) {
  if (!dbApiAvailable) {
    const error = new Error(
      "El servicio de datos no está disponible en este entorno."
    );
    error.status = 503;
    error.payload = { message: error.message };
    error.isPlotError = true;
    throw error;
  }
  const res = await fetch("/charts/data", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      Accept: "application/json",
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    if (res.status === 404 || res.status === 503) {
      dbApiAvailable = false;
    }
    let payload;
    try {
      payload = await res.json();
    } catch (_) {
      payload = await res.text();
    }
    const error = new Error("Plot API request failed");
    error.status = res.status;
    error.payload = payload;
    error.isPlotError = true;
    throw error;
  }

  /* 3.  Return plain JSON (the endpoint wraps real rows in .data) */
  return res.json(); // caller decides how to use it
}

export const getSites = () =>
  fetchDB({ table: "sites", select_columns: ["site_id", "site_name"] });

export const getDevices = (siteId) => {
  const payload = {
    table: "devices",
    select_columns: ["device_id", "device_name"],
  };

  if (siteId !== undefined && siteId !== null && siteId !== "ALL" && siteId !== "") {
    payload.filter_map = { site_id: "=" + siteId };
  }

  return fetchDB(payload);
};

export function fmtDate(d) {
  // returns "YYYY‑MM‑DD"
  return d.toISOString().slice(0, 10);
}
