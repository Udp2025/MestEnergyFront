import axios from "axios";

class AlertNotificationCenter {
  constructor() {
    this.container = document.getElementById("notifications");
    this.list = this.container?.querySelector("[data-notification-list]");
    this.emptyState = this.container?.querySelector("[data-notification-empty]");
    this.badge = document.querySelector("[data-alert-indicator]");
    this.toastHost = null;
    this.events = [];
    this.seenEventIds = new Set();
    this.initialised = false;
  }

  init() {
    if (!this.container || !this.list) {
      return;
    }
    this.setupToastContainer();
    this.refresh();
    setInterval(() => this.refresh(), 60_000);
    window.addEventListener("alerts:refresh", () => this.refresh());
  }

  setupToastContainer() {
    this.toastHost = document.getElementById("global-alert-toasts");
    if (!this.toastHost) {
      this.toastHost = document.createElement("div");
      this.toastHost.id = "global-alert-toasts";
      this.toastHost.className = "global-alert-toasts";
      document.body.appendChild(this.toastHost);
    }
  }

  async refresh() {
    try {
      const { data } = await axios.get("/api/alerts/events", {
        params: { unread_only: 1, limit: 50 },
      });
      const events = data.events || [];
      this.processNewEvents(events);
      this.events = events;
      this.render();
      this.initialised = true;
    } catch (error) {
      console.error("notifications: unable to refresh alerts", error);
    }
  }

  processNewEvents(events) {
    const currentIds = new Set(events.map((event) => event.id));
    events.forEach((event) => {
      if (!this.seenEventIds.has(event.id) && this.initialised) {
        this.showToast(event);
      }
      this.seenEventIds.add(event.id);
    });
    Array.from(this.seenEventIds).forEach((id) => {
      if (!currentIds.has(id)) {
        this.seenEventIds.delete(id);
      }
    });
  }

  render() {
    this.list.innerHTML = "";
    if (!this.events.length) {
      this.emptyState?.classList.remove("is-hidden");
      this.toggleBadge(0);
      return;
    }
    this.emptyState?.classList.add("is-hidden");
    this.toggleBadge(this.events.length);

    this.events.forEach((event) => {
      const message = this.buildMessage(event);
      const item = document.createElement("li");
      item.className = "notification-item";
      item.innerHTML = `
        <div class="notification-item__body">
          <strong>${event.kpi_name}</strong>
          <p>${message}</p>
          <small>${this.formatDate(event.triggered_at)}</small>
        </div>
        <button type="button" data-alert-read="${event.id}">OK</button>
      `;
      const button = item.querySelector("[data-alert-read]");
      button.addEventListener("click", (evt) => {
        evt.stopPropagation();
        this.markEvent(event.id);
      });
      item.addEventListener("click", (evt) => {
        if (evt.target.closest("button")) return;
        window.location.href = `/site_alerts?event=${event.id}`;
      });
      this.list.appendChild(item);
    });
  }

  async markEvent(eventId) {
    try {
      await axios.post(`/api/alerts/events/${eventId}/read`);
      this.events = this.events.filter((event) => event.id !== eventId);
      this.seenEventIds.delete(eventId);
      this.render();
    } catch (error) {
      console.error("notifications: unable to mark event as read", error);
    }
  }

  showToast(event) {
    if (!this.toastHost) return;
    const message = this.buildMessage(event);
    const toast = document.createElement("div");
    toast.className = "global-alert-toast";
    toast.innerHTML = `
      <div class="global-alert-toast__title">${event.kpi_name}</div>
      <div>${message}</div>
      <div class="global-alert-toast__actions">
        <button type="button" data-toast-view>Ver todo</button>
        <button type="button" data-toast-ok>Aceptar</button>
      </div>
    `;
    const okBtn = toast.querySelector("[data-toast-ok]");
    okBtn.addEventListener("click", async () => {
      await this.markEvent(event.id);
      toast.remove();
    });
    toast.querySelector("[data-toast-view]").addEventListener("click", () => {
      window.location.href = `/site_alerts?event=${event.id}`;
    });
    this.toastHost.appendChild(toast);
    setTimeout(() => toast.remove(), 15000);
  }

  buildMessage(event) {
    const ctx = event.context || {};
    if (ctx.missing_data) {
      return `Sin datos (${this.describeMissingReason(ctx.missing_reason)})`;
    }
    const operator = event.comparison_operator === "above" ? ">" : "<";
    return `Valor: ${event.kpi_value} · Umbral ${operator} ${event.threshold_value}`;
  }

  describeMissingReason(reason) {
    switch (reason) {
      case "no_rows":
        return "no se encontraron registros recientes";
      case "missing_column":
        return "la métrica no devolvió el campo esperado";
      case "null_value":
        return "el valor fue nulo";
      case "fetch_error":
        return "error al consultar el servicio";
      case "normalisation_failed":
        return "no se pudo interpretar el dato";
      default:
        return "dato no disponible";
    }
  }

  toggleBadge(count) {
    if (!this.badge) return;
    if (count > 0) {
      this.badge.classList.add("is-visible");
      this.badge.textContent = count;
    } else {
      this.badge.classList.remove("is-visible");
      this.badge.textContent = "";
    }
  }

  formatDate(isoString) {
    if (!isoString) {
      return "";
    }
    return new Date(isoString).toLocaleString();
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new AlertNotificationCenter().init();
});
