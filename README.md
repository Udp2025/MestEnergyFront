# MestEnergy Frontend

Aplicación Laravel 11 + Vite para la consola de MestEnergy. Incluye vistas Blade, componentes JS con Plotly y flujo de autenticación basado en roles (super admin vs. usuarios de cliente).

## Características principales
- **Inicio / Tablero fijo**: KPIs y serie de energía del día con selector de sitio para super admins (`resources/views/home.blade.php`, `resources/js/pages/home_fixed_dashboard.js`).
- **Gestión de clientes**: ficha de cliente, contratos y subida a S3 (`resources/views/clientes/*.blade.php`, `app/Http/Controllers/ClientesController.php`).
- **Configuración de usuario**: perfil, cambio de contraseña y foto a S3; gestión de usuarios por cliente (`resources/views/config*.blade.php`, `app/Http/Controllers/configController.php`).
- **Alertas y monitoreo**: alertas de sitio, detección de anomalías y forecast vía proxy ML (`resources/views/alerts.blade.php`, `resources/views/anomaly.blade.php`, `resources/views/forecast.blade.php`, `app/Http/Controllers/KpiAlertController.php`, `app/Http/Controllers/MlProxyController.php`).
- **Paneles y reportes**: widgets dinámicos y reportes automáticos con comparativas de periodo (`resources/js/pages/panels.js`, `resources/js/pages/reports.js`, `resources/js/pages/timeseries.js`).

## Stack y dependencias clave
- **Backend**: Laravel 11 (PHP ^8.2), Blade, controladores en `app/Http/Controllers`.
- **Frontend**: Vite + Laravel plugin, Plotly (`plotly.js-dist-min`), CSS en `public/css` y `resources/css`.
- **Datos y gráficos**: proxy a `/charts/plot` y `/charts/data` (Plot API) en `app/Http/Controllers/PlotProxyController.php`.
- **ML**: proxy a `/ml/forecast` y `/ml/anomaly-detection` reutilizando la misma API/key que gráficos (`MlProxyController`).
- **Almacenamiento**: S3 vía Flysystem (`league/flysystem-aws-s3-v3`), rutas de imágenes/contratos parametrizadas por env.

## Roles y alcance de datos
- **Super admin**: `cliente_id === 0`; ve todos los sitios/clientes y puede filtrar dashboards.
- **Usuarios de cliente**: limitados a su `cliente_id`/site; el proxy de datos fuerza `site_id` en todas las consultas.
- **Roles de aplicación**: `admin` y `operaciones` controlan capacidades en `/config` (crear/editar usuarios solo admin).

## Estructura de carpetas relevante
- `app/Http/Controllers`: `HomeController`, `ClientesController`, `configController`, `PlotProxyController`, `MlProxyController`, `KpiAlertController`.
- `app/Services/Plot`: `PlotClient` (llamadas remotas), `LocalPlotService` (stub local).
- `resources/views`: Blade principales (`home.blade.php`, `config_users.blade.php`, `clientes/*.blade.php`, `alerts.blade.php`, `anomaly.blade.php`, `forecast.blade.php`).
- `resources/js/pages`: entradas Vite por vista (`home_fixed_dashboard.js`, `panels.js`, `reports.js`, `anomaly.js`, `forecast.js`, `timeseries.js`).
- `public/css`: estilos globales y páginas (`style.css`, `usuarios.css`, `clientes_show.css`).
- `routes/web.php`: rutas web, middlewares y nombres de ruta usados en las vistas.
- `config/services.php`: configuración de API de gráficos/ML (`PLOT_API_BASE`, `PLOT_API_KEY`) y S3.

## Configuración y variables de entorno
Duplicar `.env.example` → `.env` y definir:
- **App**: `APP_URL`, `APP_ENV`, `APP_KEY`.
- **DB**: `DB_*` según instancia usada.
- **Plot/ML**: `PLOT_API_BASE` / `VITE_PLOT_API_BASE`, `PLOT_API_KEY` / `VITE_PLOT_API_KEY` (misma API para `/charts/*` y `/ml/*`).
- **S3**: `FILESYSTEM_DISK=s3`, `AWS_BUCKET`, `AWS_DEFAULT_REGION`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_S3_CONTRACTS_PATH`, `AWS_S3_IMAGES_PATH`.

## Puesta en marcha (dev)
```bash
composer install
npm install
cp .env.example .env  # y ajustar variables
php artisan key:generate
# si se usa base local: php artisan migrate
npm run dev           # Vite
php artisan serve     # servidor Laravel
```

## Scripts útiles
- `npm run dev` / `npm run build`: assets Vite.
- `composer test` o `php artisan test`: suite de pruebas (Pest).
- `composer dev`: servidor, cola y Vite concurrentes.

## Flujo de datos y gráficos
- Las vistas JS llaman a `/charts/data` o `/charts/plot` con `table`, `filter_map`, `aggregation`.
- `PlotProxyController` añade restricción de `site_id` para usuarios de cliente y reenvía al servicio Plot con `x-api-key`.
- ML (anomalía/forecast) sigue el mismo patrón vía `MlProxyController`.
- Sin `PLOT_API_BASE`/`PLOT_API_KEY`, el stub `LocalPlotService` devuelve datos simulados en local.

## Notas de dominio
- Tablero fijo de inicio: KPIs de PF, disponibilidad, sensores activos y energía total hoy; serie “Energía acumulada hoy” con modos agregado/por dispositivo.
- Configuración: admins pueden crear/editar/eliminar usuarios de su cliente; super admin puede elegir cliente en formularios.
- Alertas: configurables por sitio; evaluación y eventos vía `KpiAlertController` / `KpiAlertEvaluator`.
- Cargas a S3: contratos (Clientes) y fotos de perfil (Config) respetan paths definidos en env.

## Despliegue
- Empaquetar assets con `npm run build`.
- Asegurar variables `PLOT_API_BASE`/`PLOT_API_KEY` y credenciales S3 en el entorno.
- Ejecutar migraciones si aplica: `php artisan migrate --force`.

## Soporte y buenas prácticas
- Mantener roles/sitio en sesión (`site`, `is_super_admin`) para que los proxies apliquen el filtro correctamente.
- Para nuevas vistas JS, registrar la entrada en `vite.config.js` y referenciar con `@vite` en Blade.
- Reutilizar utilidades de `resources/js/utils` para selects (`fillSelect`), autenticación (`ensureAuthenticatedOrRedirect`) y Plotly (`fetchPlot`, `applyMapping`).
