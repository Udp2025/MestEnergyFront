@extends('layouts.complete')

@section('title', 'Tiggers')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Vista de Triggers - Sidebar Derecha</title>
  <link rel="stylesheet" href="{{ asset('css/tiggers.css') }}">

</head>
<body>
  <div class="main-container">
    <!-- CONTENIDO (izquierda) -->
    <div class="content">
      <!-- Barra Superior -->
      <div class="top-bar">
        <h1>Triggers</h1>
        <div class="search-container">
          <input type="text" id="searchInput" placeholder="Buscar triggers..." />
          <span class="search-icon">🔍</span>
        </div>
      </div>

      <!-- Lista de Triggers -->
      <div class="triggers-list" id="triggersList">
        <!-- Ejemplo de ítem 1 -->
        <div class="trigger-item"
             data-severity="low"
             data-user="Gil Cohen"
             data-status="draft"
             data-category="hardware"
             data-title="test">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-low"></span>
              test
            </div>
            <div class="trigger-subtitle">Dye House Roof | Power is Above 2W for 20 min</div>
            <div class="trigger-details">Creado por: Gil Cohen | Status: Draft</div>
          </div>
          <div class="trigger-actions">
            <!-- Estado -->
            <span class="trigger-status status-draft">Draft</span>
            <!-- Icono de "ojo" en SVG -->
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <!-- Ejemplo de ítem 2 -->
        <div class="trigger-item"
             data-severity="medium"
             data-user="Aida Gerssman"
             data-status="active"
             data-category="maintenance"
             data-title="Boiler reaching upper power threshold">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-medium"></span>
              Boiler reaching upper power threshold
            </div>
            <div class="trigger-subtitle">Boiler 1 Feed Water Pumps</div>
            <div class="trigger-details">
              Power is Above 19.5A for more than 10min during Site from 10/01/2025
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-active">Active</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <!-- Ejemplo de ítem 3 -->
        <div class="trigger-item"
             data-severity="high"
             data-user="John Archer"
             data-status="active"
             data-category="hardware"
             data-title="Conveyor Belt 22d has exceeded power threshold">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-high"></span>
              Conveyor Belt 22d has exceeded power threshold
            </div>
            <div class="trigger-subtitle">Pre-Treatment Line Mains</div>
            <div class="trigger-details">
              Power above 30kW for 30 minutes | Creado por: John Archer
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-active">Active</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <!-- Ejemplo de ítem 4 -->
        <div class="trigger-item"
             data-severity="critical"
             data-user="Maggie Recording"
             data-status="active"
             data-category="software"
             data-title="Motor operation out of boundary">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-critical"></span>
              Motor operation out of boundary
            </div>
            <div class="trigger-subtitle">Boiler 2 Feed</div>
            <div class="trigger-details">
              Power is Above 1kW | Creado por: Maggie Recording
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-active">Active</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <!-- Más ejemplos -->
        <div class="trigger-item"
             data-severity="medium"
             data-user="Alex Fu"
             data-status="draft"
             data-category="software"
             data-title="Excessive water usage in Dye House">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-medium"></span>
              Excessive water usage in Dye House
            </div>
            <div class="trigger-subtitle">Dye House - Water Flow Sensor</div>
            <div class="trigger-details">
              Usage above 3000L/h for 15 min
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-draft">Draft</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <div class="trigger-item"
             data-severity="high"
             data-user="Maggie Recording"
             data-status="active"
             data-category="maintenance"
             data-title="Temperature sensor reading too high">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-high"></span>
              Temperature sensor reading too high
            </div>
            <div class="trigger-subtitle">Main Boiler Thermostat</div>
            <div class="trigger-details">
              Above 250°C for 10 minutes
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-active">Active</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <div class="trigger-item"
             data-severity="low"
             data-user="John Archer"
             data-status="archived"
             data-category="hardware"
             data-title="Old trigger - test only">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-low"></span>
              Old trigger - test only
            </div>
            <div class="trigger-subtitle">Archived trigger example</div>
            <div class="trigger-details">
              No longer in use
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-archived">Archived</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>

        <div class="trigger-item"
             data-severity="critical"
             data-user="Aida Gerssman"
             data-status="active"
             data-category="maintenance"
             data-title="Critical: Overflow in chemical tank">
          <div class="trigger-info">
            <div class="trigger-title">
              <span class="severity-icon severity-critical"></span>
              Critical: Overflow in chemical tank
            </div>
            <div class="trigger-subtitle">Chemical Tank #3</div>
            <div class="trigger-details">
              Liquid level above safety threshold
            </div>
          </div>
          <div class="trigger-actions">
            <span class="trigger-status status-active">Active</span>
            <a href="#" class="eye-icon" title="Ver detalles">
              <svg
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
              >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- SIDEBAR (derecha) -->
    <div class="sidebar">
      <h3>Filtros</h3>

      <!-- Botón Borrar todo -->
      <button class="clear-all" id="clearAllFilters">Borrar todo</button>

      <!-- Filtros por severidad -->
      <div class="filters-group" id="severityGroup">
        <h4>Severity <span class="toggle-arrow">▼</span></h4>
        <div class="filters-content" id="severityFilters">
          <div class="filter-item">
            <input type="checkbox" id="severityCritical" data-severity="critical" />
            <label for="severityCritical">Critical</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="severityHigh" data-severity="high" />
            <label for="severityHigh">High</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="severityMedium" data-severity="medium" />
            <label for="severityMedium">Medium</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="severityLow" data-severity="low" />
            <label for="severityLow">Low</label>
          </div>
        </div>
      </div>

      <!-- Filtros por usuario -->
      <div class="filters-group" id="userGroup">
        <h4>Usuarios <span class="toggle-arrow">▼</span></h4>
        <div class="filters-content" id="userFilters">
          <div class="filter-item">
            <input type="checkbox" id="userGil" data-user="Gil Cohen" />
            <label for="userGil">Gil Cohen</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="userAida" data-user="Aida Gerssman" />
            <label for="userAida">Aida Gerssman</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="userJohn" data-user="John Archer" />
            <label for="userJohn">John Archer</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="userMaggie" data-user="Maggie Recording" />
            <label for="userMaggie">Maggie Recording</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="userAlex" data-user="Alex Fu" />
            <label for="userAlex">Alex Fu</label>
          </div>
        </div>
      </div>

      <!-- Filtros por estado -->
      <div class="filters-group" id="statusGroup">
        <h4>Status <span class="toggle-arrow">▼</span></h4>
        <div class="filters-content" id="statusFilters">
          <div class="filter-item">
            <input type="checkbox" id="statusDraft" data-status="draft" />
            <label for="statusDraft">Draft</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="statusActive" data-status="active" />
            <label for="statusActive">Active</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="statusArchived" data-status="archived" />
            <label for="statusArchived">Archived</label>
          </div>
        </div>
      </div>

      <!-- Filtro adicional (Categoría) -->
      <div class="filters-group" id="categoryGroup">
        <h4>Categoría <span class="toggle-arrow">▼</span></h4>
        <div class="filters-content" id="categoryFilters">
          <div class="filter-item">
            <input type="checkbox" id="catHardware" data-category="hardware" />
            <label for="catHardware">Hardware</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="catSoftware" data-category="software" />
            <label for="catSoftware">Software</label>
          </div>
          <div class="filter-item">
            <input type="checkbox" id="catMaintenance" data-category="maintenance" />
            <label for="catMaintenance">Mantenimiento</label>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
<script src="{{ asset('js/tiggers.js') }}"></script>

@endsection
