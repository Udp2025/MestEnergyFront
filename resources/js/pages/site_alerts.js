import axios from "axios";
import { getSites } from "../utils/core";
import { canViewAllSites } from "../utils/auth";

const state = {
  definitions: new Map(),
  alerts: [],
  sites: [],
  editingId: null,
  isSuperAdmin: canViewAllSites(),
  recentEvents: [],
  eventMap: new Map(),
  eventFilterSite: "ALL",
};

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("alertForm");
  const kpiSelect = document.getElementById("kpiSlug");
  const operatorSelect = document.getElementById("comparisonOperator");
  const thresholdInput = document.getElementById("thresholdValue");
  const cooldownInput = document.getElementById("cooldownMinutes");
  const unitAddon = document.getElementById("thresholdUnit");
  const siteSelector = document.getElementById("siteSelector");
  const eventFilterSelect = document.getElementById("eventSiteFilter");
  const formNotice = document.getElementById("alertFormNotice");
  const resetBtn = document.getElementById("resetForm");
  const alertsTableBody = document.getElementById("alertsTableBody");
  const totalsChip = document.querySelector("[data-alert-count]");
  const totalsLabel = document.getElementById("alertsTotal");
  const toastContainer = document.getElementById("alertToastContainer");
  const eventsEmpty = toastContainer?.querySelector("[data-events-empty]");
  const detailModal = document.getElementById("alertDetailModal");
  const detailFields = {
    title: document.getElementById("detailTitle"),
    subtitle: document.getElementById("detailSubtitle"),
    condition: document.getElementById("detailCondition"),
    value: document.getElementById("detailValue"),
    site: document.getElementById("detailSite"),
    timestamp: document.getElementById("detailTimestamp"),
    status: document.getElementById("detailStatus"),
    markButton: detailModal?.querySelector("[data-detail-mark]"),
    closeButtons: detailModal?.querySelectorAll("[data-detail-close]"),
  };

  async function init() {
    await fetchDefinitions();
    if (state.isSuperAdmin) {
      await loadSites();
    } else if (siteSelector) {
      siteSelector.closest(".form-group").style.display = "none";
    }
    await loadAlerts();
    await refreshEvents();
    window.setInterval(refreshEvents, 60_000);
  }

  async function fetchDefinitions() {
    try {
      const { data } = await axios.get("/api/alerts/definitions");
      state.definitions = new Map(
        data.definitions.map((definition) => [definition.slug, definition])
      );
      renderDefinitions();
    } catch (error) {
      console.error("Unable to load alert definitions", error);
      formNotice.textContent =
        "No se pudieron cargar los indicadores disponibles.";
    }
  }

  function renderDefinitions() {
    if (!kpiSelect) return;
    kpiSelect.innerHTML = "";
    state.definitions.forEach((definition, slug) => {
      const option = document.createElement("option");
      option.value = slug;
      option.textContent = definition.name;
      kpiSelect.appendChild(option);
    });
    const firstSlug = kpiSelect.value;
    applyDefinitionDefaults(firstSlug);
  }

  function applyDefinitionDefaults(slug) {
    const definition = state.definitions.get(slug);
    if (!definition) return;
    const description = document.getElementById("kpiDescription");
    if (description) {
      description.textContent = definition.description || "";
    }
    if (unitAddon) {
      unitAddon.textContent = definition.unit || "—";
    }
    operatorSelect.value = definition.default_operator || "above";
    thresholdInput.value = definition.default_threshold ?? "";
    if (state.isSuperAdmin) {
      const requiresSite = definition.supports_site_selection;
      siteSelector.disabled = !requiresSite || !state.sites.length;
      siteSelector.parentElement.classList.toggle(
        "is-hidden",
        !requiresSite
      );
    }
  }

  async function loadSites() {
    try {
      const rows = await getSites();
      const list = Array.isArray(rows?.data) ? rows.data : rows;
      state.sites = list.map((row) => ({
        id: row.site_id ?? row.id,
        name: row.site_name ?? `Sitio ${row.site_id ?? row.id}`,
      }));
      populateSiteSelector();
    } catch (error) {
      console.error("site_alerts: unable to fetch sites", error);
    }
  }

  function populateSiteSelector() {
    if (!siteSelector) return;
    siteSelector.innerHTML = '<option value="">Selecciona un sitio</option>';
    state.sites.forEach((site) => {
      const option = document.createElement("option");
      option.value = site.id;
      option.textContent = site.name;
      siteSelector.appendChild(option);
    });
    siteSelector.disabled = false;
    populateEventFilter();
  }

  function populateEventFilter() {
    if (!eventFilterSelect) return;
    eventFilterSelect.innerHTML = '<option value="ALL">Todos los sitios</option>';
    state.sites.forEach((site) => {
      const option = document.createElement("option");
      option.value = site.id;
      option.textContent = site.name;
      eventFilterSelect.appendChild(option);
    });
    eventFilterSelect.value = state.eventFilterSite || "ALL";
  }

  async function loadAlerts() {
    try {
      const { data } = await axios.get("/api/alerts");
      state.alerts = data.alerts || [];
      renderAlerts();
    } catch (error) {
      console.error("site_alerts: unable to fetch alerts", error);
      setNotice("No se pudieron cargar tus alertas.", "error");
    }
  }

  function renderAlerts() {
    if (!alertsTableBody) return;
    alertsTableBody.innerHTML = "";
    if (!state.alerts.length) {
      const emptyRow = document.createElement("tr");
      const cell = document.createElement("td");
      cell.colSpan = 5;
      cell.textContent = "Aún no has creado alertas.";
      emptyRow.appendChild(cell);
      alertsTableBody.appendChild(emptyRow);
    } else {
      state.alerts.forEach((alert) => {
        alertsTableBody.appendChild(renderAlertRow(alert));
      });
    }

    const activeCount = state.alerts.filter((alert) => alert.is_active).length;
    if (totalsChip) {
      totalsChip.textContent = state.alerts.length;
    }
    if (totalsLabel) {
      totalsLabel.textContent = `${activeCount} / ${state.alerts.length} alertas activas`;
    }
  }

  function getSiteLabel(siteId) {
    if (!siteId) return "";
    const match = state.sites.find(
      (site) => String(site.id) === String(siteId)
    );
    return match ? match.name : `Sitio ${siteId}`;
  }

  function renderAlertRow(alert) {
    const definition = state.definitions.get(alert.kpi_slug) || {};
    const row = document.createElement("tr");
    const unit = definition.unit ? ` ${definition.unit}` : "";
    const operatorLabel =
      alert.comparison_operator === "above" ? ">" : "<";
    const siteLabel =
      state.isSuperAdmin && alert.site_id
        ? ` • ${getSiteLabel(alert.site_id)}`
        : "";

    row.innerHTML = `
      <td>
        <div class="alert-row__metric">
          <strong>${definition.name || alert.kpi_slug}</strong>
          <small>${definition.description || ""}${siteLabel}</small>
        </div>
      </td>
      <td>${operatorLabel} ${alert.threshold_value}${unit}</td>
      <td>${alert.cooldown_minutes} min</td>
      <td>
        <span class="alert-row__status ${
          alert.is_active
            ? "alert-row__status--active"
            : "alert-row__status--paused"
        }">${alert.is_active ? "Activa" : "Pausada"}</span>
      </td>
      <td class="alert-row__actions"></td>
    `;

    const actionCell = row.querySelector(".alert-row__actions");
    const buttons = document.createElement("div");
    buttons.className = "alert-row__actions-wrapper";

    const editBtn = document.createElement("button");
    editBtn.textContent = "Editar";
    editBtn.addEventListener("click", () => populateForm(alert));

    const toggleBtn = document.createElement("button");
    toggleBtn.textContent = alert.is_active ? "Pausar" : "Activar";
    toggleBtn.addEventListener("click", () => toggleAlert(alert));

    const deleteBtn = document.createElement("button");
    deleteBtn.textContent = "Eliminar";
    deleteBtn.addEventListener("click", () => removeAlert(alert));

    buttons.append(editBtn, toggleBtn, deleteBtn);
    actionCell.appendChild(buttons);

    return row;
  }

  function populateForm(alert) {
    state.editingId = alert.id;
    kpiSelect.value = alert.kpi_slug;
    operatorSelect.value = alert.comparison_operator;
    thresholdInput.value = alert.threshold_value;
    cooldownInput.value = alert.cooldown_minutes;
    if (state.isSuperAdmin && alert.site_id) {
      siteSelector.value = alert.site_id;
    }
    applyDefinitionDefaults(alert.kpi_slug);
    setNotice(`Editando alerta para ${alert.kpi_slug}`, "info");
    document.getElementById("saveAlert").textContent = "Actualizar alerta";
  }

  function resetFormFields() {
    state.editingId = null;
    form.reset();
    document.getElementById("alertId").value = "";
    const firstSlug = kpiSelect.options[0]?.value;
    if (firstSlug) {
      kpiSelect.value = firstSlug;
      applyDefinitionDefaults(firstSlug);
    }
    if (state.isSuperAdmin) {
      siteSelector.value = "";
    }
    document.getElementById("saveAlert").textContent = "Guardar alerta";
    setNotice("", "info");
  }

  async function toggleAlert(alert) {
    try {
      await axios.patch(`/api/alerts/${alert.id}`, {
        is_active: !alert.is_active,
      });
      await loadAlerts();
    } catch (error) {
      console.error("Unable to toggle alert", error);
    }
  }

  async function removeAlert(alert) {
    if (!confirm("¿Eliminar esta alerta?")) return;
    try {
      await axios.delete(`/api/alerts/${alert.id}`);
      if (state.editingId === alert.id) {
        resetFormFields();
      }
      await loadAlerts();
    } catch (error) {
      console.error("Unable to delete alert", error);
      setNotice("No se pudo eliminar la alerta.", "error");
    }
  }

  async function refreshEvents() {
    try {
      const params = { unread_only: 1, limit: 50 };
      if (
        state.isSuperAdmin &&
        state.eventFilterSite &&
        state.eventFilterSite !== "ALL"
      ) {
        params.site_id = state.eventFilterSite;
      }
      const { data } = await axios.get("/api/alerts/events", {
        params,
      });
      const events = data.events || [];
      state.recentEvents = events;
      state.eventMap = new Map(events.map((evt) => [evt.id, evt]));
      renderEvents(events);
      const focusId = new URLSearchParams(window.location.search).get("event");
      if (focusId && state.eventMap.has(Number(focusId))) {
        openEventDetails(state.eventMap.get(Number(focusId)));
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete("event");
        window.history.replaceState({}, "", newUrl);
      }
    } catch (error) {
      console.error("site_alerts: unable to fetch events", error);
    }
  }

  function renderEvents(events) {
    if (!toastContainer) return;
    toastContainer.querySelectorAll(".alert-toast").forEach((node) => node.remove());
    if (!events.length) {
      eventsEmpty?.classList.remove("is-hidden");
      return;
    }
    eventsEmpty?.classList.add("is-hidden");
    events.forEach((event) => {
      const toast = document.createElement("article");
      toast.className = "alert-toast";
      toast.innerHTML = `
        <div class="alert-toast__body">
          <strong>${event.kpi_name}</strong>
          <p>${describeEventMessage(event)}</p>
          <small>${formatDate(event.triggered_at)}</small>
        </div>
        <div class="alert-toast__actions">
          <button type="button" data-event-detail="${event.id}">Detalles</button>
          <button type="button" data-event-read="${event.id}">Marcar como leído</button>
        </div>
      `;
      toast
        .querySelector("[data-event-read]")
        .addEventListener("click", (evt) => {
          evt.stopPropagation();
          markEvent(event.id);
        });
      toast
        .querySelector("[data-event-detail]")
        .addEventListener("click", (evt) => {
          evt.stopPropagation();
          openEventDetails(event);
        });
      toast.addEventListener("click", () => openEventDetails(event));
      toastContainer.appendChild(toast);
    });
  }

  async function markEvent(eventId) {
    try {
      await axios.post(`/api/alerts/events/${eventId}/read`);
      await refreshEvents();
      window.dispatchEvent(new Event("alerts:refresh"));
    } catch (error) {
      console.error("site_alerts: unable to mark event", error);
    }
  }

  async function markAllEvents() {
    try {
      await axios.post("/api/alerts/events/read-all");
      await refreshEvents();
      window.dispatchEvent(new Event("alerts:refresh"));
    } catch (error) {
      console.error("site_alerts: unable to mark all events", error);
    }
  }

  function formatDate(isoString) {
    if (!isoString) return "";
    const date = new Date(isoString);
    return date.toLocaleString();
  }

  function describeEventMessage(event) {
    const ctx = event.context || {};
    if (ctx.missing_data) {
      return `Sin datos (${describeMissingReason(ctx.missing_reason)})`;
    }
    const operator = event.comparison_operator === "above" ? ">" : "<";
    return `Valor actual: <b>${event.kpi_value}</b> — Umbral ${operator} ${event.threshold_value}`;
  }

  function describeMissingReason(reason = "") {
    switch (reason) {
      case "no_rows":
        return "No se encontraron registros recientes";
      case "missing_column":
        return "La métrica no devolvió el campo esperado";
      case "null_value":
        return "El valor fue nulo";
      case "fetch_error":
        return "Error al consultar el servicio";
      case "normalisation_failed":
        return "No se pudo interpretar el dato";
      default:
        return "Dato no disponible";
    }
  }

  function setNotice(message, type = "info") {
    if (!formNotice) return;
    formNotice.textContent = message || "";
    formNotice.dataset.type = type;
  }

  function openEventDetails(event) {
    if (!detailModal) return;
    detailModal.classList.add("is-open");
    detailModal.dataset.eventId = event.id;
    const definition = state.definitions.get(event.kpi_slug) || {};
    detailFields.title.textContent = event.kpi_name;
    detailFields.subtitle.textContent = definition.description || "";
    detailFields.condition.textContent = formatCondition(event, definition);
    detailFields.value.textContent = describeEventValue(event, definition);
    detailFields.site.textContent = formatSite(event);
    detailFields.timestamp.textContent = formatDate(event.triggered_at);
    detailFields.status.textContent = event.read_at ? "Leída" : "Sin leer";
  }

  function closeEventDetails() {
    if (!detailModal) return;
    detailModal.classList.remove("is-open");
    delete detailModal.dataset.eventId;
  }

  function formatCondition(event, definition = {}) {
    const ctx = event.context || {};
    if (ctx.missing_data) {
      return "Revisar disponibilidad de datos";
    }
    const operator = event.comparison_operator === "above" ? ">" : "<";
    const unit = definition.unit ? ` ${definition.unit}` : "";
    return `${operator} ${event.threshold_value}${unit}`;
  }

  function describeEventValue(event, definition = {}) {
    const ctx = event.context || {};
    if (ctx.missing_data) {
      return describeMissingReason(ctx.missing_reason);
    }
    const unit = definition.unit ? ` ${definition.unit}` : "";
    return `${event.kpi_value}${unit}`;
  }

  function formatSite(event) {
    const siteId = event.context?.site_id;
    if (!siteId) return "Predeterminado";
    const match = state.sites.find((site) => String(site.id) === String(siteId));
    return match ? match.name : `Sitio ${siteId}`;
  }

  async function submitForm(event) {
    event.preventDefault();
    const payload = {
      kpi_slug: kpiSelect.value,
      comparison_operator: operatorSelect.value,
      threshold_value: Number(thresholdInput.value),
      cooldown_minutes: Number(cooldownInput.value) || undefined,
    };
    if (state.isSuperAdmin) {
      payload.site_id = siteSelector.value || null;
    }
    try {
      if (state.editingId) {
        await axios.patch(`/api/alerts/${state.editingId}`, payload);
        setNotice("Alerta actualizada correctamente.", "success");
      } else {
        await axios.post("/api/alerts", payload);
        setNotice("Alerta creada.", "success");
      }
      await loadAlerts();
      resetFormFields();
    } catch (error) {
      console.error("site_alerts: unable to save alert", error);
      const message =
        error.response?.data?.message || "No se pudo guardar la alerta.";
      setNotice(message, "error");
    }
  }

  document
    .getElementById("markAllEvents")
    ?.addEventListener("click", markAllEvents);
  document
    .querySelector("[data-refresh-alerts]")
    ?.addEventListener("click", () => {
      loadAlerts();
      refreshEvents();
    });
  form?.addEventListener("submit", submitForm);
  kpiSelect?.addEventListener("change", (event) =>
    applyDefinitionDefaults(event.target.value)
  );
  resetBtn?.addEventListener("click", resetFormFields);
  eventFilterSelect?.addEventListener("change", (event) => {
    state.eventFilterSite = event.target.value || "ALL";
    refreshEvents();
  });
  detailFields.closeButtons?.forEach((button) =>
    button.addEventListener("click", closeEventDetails)
  );
  detailFields.markButton?.addEventListener("click", async () => {
    const eventId = Number(detailModal?.dataset.eventId);
    if (eventId) {
      await markEvent(eventId);
    }
    closeEventDetails();
  });

  init();
});
