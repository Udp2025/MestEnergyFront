@extends('layouts.complete')

@section('title', 'Groups')

@section('content')
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Medidas de Energía</title>
  <!-- FontAwesome para iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/groups.css') }}">  
</head>
<body>

  <!-- HEADER: Título y barra de búsqueda -->
  <header class="header">
    <div class="header-left">
      <h1>Medidas de Energía</h1>
    </div>
    <div class="header-right">
      <input type="text" id="search" placeholder="Buscar..." oninput="filterData()">
    </div>
  </header>

  <!-- CONTENEDOR DE FILTROS, centrado y separado -->
  <div class="filters-container">
    <span class="filter active" onclick="filterByType('')">
      <i class="fas fa-list"></i> Todos
    </span>
    <span class="filter" onclick="filterByType('Medida')">
      <i class="fas fa-bolt"></i> Medida
    </span>
    <span class="filter" onclick="filterByType('Generación')">
      <i class="fas fa-sun"></i> Generación
    </span>
    <span class="filter" onclick="filterByType('Eficiencia')">
      <i class="fas fa-leaf"></i> Eficiencia
    </span>
    <span class="filter" onclick="filterByType('Análisis')">
      <i class="fas fa-chart-pie"></i> Análisis
    </span>
  </div>

  <!-- CONTENEDOR DE TARJETAS -->
  <section class="card-container" id="cardContainer">
    <!-- Las tarjetas se inyectan aquí con JavaScript -->
  </section>
</body>
</html>
<script>
  // Datos de ejemplo actualizados a temática de energía y medidas, cada uno con su ruta "View"
const measures = [
  {
    name: "Consumo Eléctrico",
    type: "Medida",
    icon: "fas fa-bolt",
    description: "Medición en tiempo real del consumo eléctrico de una instalación.",
    route: "/measures/consumo-electrico"
  },
  {
    name: "Generación Solar",
    type: "Generación",
    icon: "fas fa-sun",
    description: "Monitoreo de la producción de energía solar mediante paneles fotovoltaicos.",
    route: "/measures/generacion-solar"
  },
  {
    name: "Generación Eólica",
    type: "Generación",
    icon: "fas fa-wind",
    description: "Análisis de la generación de energía eólica a través de turbinas.",
    route: "/measures/generacion-eolica"
  },
  {
    name: "Eficiencia Energética",
    type: "Eficiencia",
    icon: "fas fa-leaf",
    description: "Evaluación de la eficiencia en el uso y ahorro de la energía.",
    route: "/measures/eficiencia-energetica"
  },
  {
    name: "Medición de Voltaje",
    type: "Medida",
    icon: "fas fa-chart-line",
    description: "Registro de voltajes en distintos puntos de la red eléctrica.",
    route: "/measures/medicion-voltaje"
  },
  {
    name: "Análisis de Consumo",
    type: "Análisis",
    icon: "fas fa-chart-pie",
    description: "Estudio detallado del consumo energético a lo largo del tiempo.",
    route: "/measures/analisis-consumo"
  }
];

let activeType = "";   // Filtro activo (vacío = todos)
let searchQuery = "";  // Texto de búsqueda

// Función para renderizar las tarjetas según filtros y búsqueda
function renderCards() {
  const container = document.getElementById("cardContainer");
  container.innerHTML = "";  // Limpiar contenido

  measures
    .filter(item => {
      const matchesType = activeType === "" || item.type === activeType;
      const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
      return matchesType && matchesSearch;
    })
    .forEach(item => {
      // Crear la tarjeta y su estructura interna
      const card = document.createElement("div");
      card.className = "card";
      card.innerHTML = `
        <div class="card-left">
          <i class="icon ${item.icon}"></i>
          <div class="card-info">
            <h2>${item.name}</h2>
            <p class="card-type">${item.type}</p>
            <div class="card-description">${item.description}</div>
          </div>
        </div>
        <div class="card-options">
          <i class="fas fa-ellipsis-h option-toggle"></i>
          <div class="dropdown-menu">
            <a href="{{ route ('areas_carga.index')}}"><i class="fas fa-eye"></i> View</a>
          </div>
        </div>
      `;
      // Al hacer clic en la tarjeta se muestra u oculta la descripción
      card.addEventListener("click", (e) => {
        // Evitamos que el clic en la opción detone el toggle de la descripción
        if (!e.target.classList.contains("option-toggle") && !e.target.closest(".dropdown-menu")) {
          card.classList.toggle("active");
        }
      });
      // Manejamos el toggle del dropdown de opciones
      const optionToggle = card.querySelector(".option-toggle");
      const dropdownMenu = card.querySelector(".dropdown-menu");
      optionToggle.addEventListener("click", (e) => {
        e.stopPropagation(); // Para que no se active el toggle de la descripción
        // Cierra otros dropdowns abiertos
        document.querySelectorAll(".dropdown-menu").forEach(menu => {
          if (menu !== dropdownMenu) menu.style.display = "none";
        });
        // Toggle del dropdown actual
        dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
      });
      // Cierra el dropdown si se hace clic en cualquier otro lugar de la tarjeta
      card.addEventListener("click", () => {
        dropdownMenu.style.display = "none";
      });
      container.appendChild(card);
    });
}

// Función para filtrar por tipo según el filtro clicado
function filterByType(type) {
  activeType = type;
  // Actualizamos la clase active en los filtros
  document.querySelectorAll(".filters-container .filter").forEach(el => {
    el.classList.remove("active");
    if ((type === "" && el.textContent.trim().includes("Todos")) || el.textContent.trim().includes(type)) {
      el.classList.add("active");
    }
  });
  renderCards();
}

// Función para filtrar según el texto de búsqueda
function filterData() {
  searchQuery = document.getElementById("search").value;
  renderCards();
}

// Renderizamos las tarjetas al cargar la página
renderCards();

// Opcional: cerrar dropdowns si se hace clic fuera de ellos
document.addEventListener("click", (e) => {
  document.querySelectorAll(".dropdown-menu").forEach(menu => {
    if (!menu.contains(e.target)) {
      menu.style.display = "none";
    }
  });
});
 
</script>


@endsection
