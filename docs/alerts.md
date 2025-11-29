## KPI Alerts Overview

The alert system lets every user subscribe to KPI thresholds and receive in-app notifications when those conditions are met. Alerts are evaluated on demand whenever the frontend polls `/api/alerts/events`.

### Tables

```sql
CREATE TABLE `kpi_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `kpi_slug` varchar(255) NOT NULL,
  `comparison_operator` enum('above','below') NOT NULL DEFAULT 'above',
  `threshold_value` double NOT NULL,
  `site_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cooldown_minutes` smallint unsigned NOT NULL DEFAULT 30,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_value` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_alerts_user_id_kpi_slug_index` (`user_id`,`kpi_slug`),
  CONSTRAINT `kpi_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

```sql
CREATE TABLE `kpi_alert_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kpi_alert_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `kpi_value` double NOT NULL,
  `context` json DEFAULT NULL,
  `triggered_at` timestamp NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_alert_events_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `kpi_alert_events_kpi_alert_id_foreign` FOREIGN KEY (`kpi_alert_id`) REFERENCES `kpi_alerts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kpi_alert_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

### Evaluation Flow

1. The frontend polls `/api/alerts/events`.  
2. `KpiAlertEvaluator` fetches the latest KPI values using the same Plot service used by dashboards.  
3. Each alert compares the current value against the stored threshold.  
4. When the condition is met and the alert is not on cooldown, a `kpi_alert_events` record is created and the alert’s `last_triggered_at` timestamp is refreshed.  
5. Events stay unread until the user marks them via `POST /api/alerts/events/{event}/read`.  

The evaluator supports several KPI types (availability, energy, load factor, PF compliance, data freshness, and active devices). New KPIs can be added by appending their definition to `config/kpi_alerts.php`.

### Missing Data Policy

- If the KPI query returns no rows, no value for the requested column, or a null entry, the evaluator creates an alert event flagged with `missing_data = true`.  
- Missing-data alerts respect the standard cooldown so users are not spammed, but they clearly state that the issue is “Sin datos” in the bell dropdown and `/site_alerts`.  
- These events behave like regular notifications: they appear unread, can be marked as read, and remain visible in the activity list.

### Running the Evaluator

- Alerts are evaluated automatically every 15 minutes through the scheduled command defined in `bootstrap/app.php` (`kpi:run-alerts`). Make sure your cron runs `php artisan schedule:run` every minute so the scheduler can trigger it.  
- You can also run the evaluator manually:
  - `php artisan kpi:run-alerts` – run for all users with active alerts.
  - `php artisan kpi:run-alerts --user=123` – limit the run to a specific user ID.

### Endpoints

| Method & Path | Description |
| --- | --- |
| `GET /api/alerts/definitions` | Lists KPI metadata (used to build the UI form). |
| `GET /api/alerts` | Returns all alerts for the authenticated user. |
| `POST /api/alerts` | Creates a new alert (per-user). |
| `PATCH /api/alerts/{alert}` | Updates an existing alert. |
| `DELETE /api/alerts/{alert}` | Removes an alert. |
| `GET /api/alerts/events` | Evaluates alerts and returns unread/ recent events. |
| `POST /api/alerts/events/{event}/read` | Marks a notification as read. |

### Frontend

- `/site_alerts` provides management UI (create/update/delete alerts).  
- The global bell icon polls `/api/alerts/events?unread_only=1` every minute and surfaces unread events within the dropdown.  
- Alerts are also shown as inline toasts on the `/site_alerts` page for quick acknowledgement.

### Manual Testing Tips

1. Create an alert with a numeric threshold (e.g., availability below 90%). Either adjust sample data or temporarily edit the KPI source so that the value breaches the threshold. Run `php artisan kpi:run-alerts` and reload the bell dropdown to confirm the notification shows the measured value vs. the configured threshold.
2. Create another alert where the KPI column is known to be null/missing (or temporarily comment out the value in the dataset). Run the command again: the notification should read “Sin datos …” and include the missing-data reason. Mark the event as read from the dropdown or the `/site_alerts` toast area to ensure the unread counter clears.
