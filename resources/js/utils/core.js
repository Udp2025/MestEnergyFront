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

export async function fetchDB(body) {
  /* 1.  Read base URL & key from Vite env (exposed by Laravel-Vite) */
  const BASE = import.meta.env.VITE_PLOT_API_BASE;
  const KEY = import.meta.env.VITE_PLOT_API_KEY;

  if (!BASE || !KEY) {
    throw new Error("Plot API env vars are missing (check .env & Vite config)");
  }

  /* 2.  POST to the universal items/data endpoint */
  const res = await fetch(`${BASE}/items/data`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "x-api-key": KEY,
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const msg = await res.text();
    throw new Error(`API ${res.status}: ${msg}`);
  }

  /* 3.  Return plain JSON (the endpoint wraps real rows in .data) */
  return res.json(); // caller decides how to use it
}

export const getSites = () =>
  fetchDB({ table: "sites", select_columns: ["site_id", "site_name"] });

export const getDevices = (siteId) =>
  fetchDB({
    table: "devices",
    filter_map: { site_id: "=" + siteId },
    select_columns: ["device_id", "device_name"],
  });

export function fmtDate(d) {
  // returns "YYYY‑MM‑DD"
  return d.toISOString().slice(0, 10);
}
