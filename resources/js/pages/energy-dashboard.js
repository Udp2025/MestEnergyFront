import {
  ensureAuthenticatedOrRedirect,
  canViewAllSites,
  currentUserSiteId,
} from "../utils/auth";

import { getSites, fetchDB } from "../utils/core";
import { fillSelect } from "../utils/list";


async function getDevicesBySite(siteId) {
  const payload = {
    table: "devices",
    select_columns: ["device_id", "device_name"],
  };

  if (siteId && siteId !== "ALL") {
    payload.filter_map = { site_id: "=" + siteId };
  }

  return fetchDB(payload);
}

async function hydrateSites(select) {
  if (!select) return null;

  try {
    const sites = await getSites();
    const rows = Array.isArray(sites?.data)
      ? sites.data
      : Array.isArray(sites)
      ? sites
      : [];

    if (!rows.length) {
      const siteId = currentUserSiteId();
      if (siteId) {
        select.innerHTML = `<option value="${siteId}" selected>Sitio ${siteId}</option>`;
        return siteId;
      }
      return null;
    }

    fillSelect(select, rows, "site_id", "site_name");

    let selectedSite = null;

    if (canViewAllSites()) {
      select.insertAdjacentHTML(
        "afterbegin",
        '<option value="">Todos los sitios</option>'
      );
      selectedSite = String(rows[0]?.site_id ?? "");
      select.value = selectedSite;
    } else {
      selectedSite = String(currentUserSiteId());
      if (selectedSite) select.value = selectedSite;
    }

    return selectedSite;
  } catch (error) {
    console.warn("energy-dashboard: no se pudieron cargar sitios", error);
    return null;
  }
}

async function hydrateDevices(siteId, select) {
  if (!siteId || !select) return;

  select.innerHTML = "<option>Cargando sensores...</option>";
  select.disabled = true;

  try {
    const res = await getDevicesBySite(siteId);
    const rows = Array.isArray(res?.data)
      ? res.data
      : Array.isArray(res)
      ? res
      : [];

    // Caso: el sitio no tiene sensores
    if (!rows.length) {
      select.innerHTML = "<option value=''>Sin sensores</option>";
      select.disabled = true;
      return;
    }

    // Llenar sensores
    fillSelect(select, rows, "device_id", "device_name");

    // 👉 OPCIÓN CLAVE
    select.insertAdjacentHTML(
      "afterbegin",
      '<option value="">Todos los sensores</option>'
    );

    // Por default: todos los sensores (NO envía device_id)
    select.value = "";
    select.disabled = false;

  } catch (err) {
    console.warn("energy-dashboard: error cargando sensores", err);
    select.innerHTML = "<option value=''>Error al cargar sensores</option>";
    select.disabled = true;
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  const siteSelect = document.getElementById("site-select");
  const deviceSelect = document.getElementById("device-select");

  const initialSiteId = await hydrateSites(siteSelect);

  if (initialSiteId) {
    hydrateDevices(initialSiteId, deviceSelect);
  }

  siteSelect.addEventListener("change", () => {
    const siteId = siteSelect.value;

    deviceSelect.innerHTML = "";
    deviceSelect.disabled = true;

    if (siteId) {
      hydrateDevices(siteId, deviceSelect);
    }
  });
});

