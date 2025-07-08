 // ------------------------------------------------------------------
// 1) Lógica de plegado/desplegado de filtros
// ------------------------------------------------------------------
const filterGroups = document.querySelectorAll('.filters-group');
filterGroups.forEach(group => {
  const header = group.querySelector('h4');
  header.addEventListener('click', () => {
    group.classList.toggle('open');
    header.classList.toggle('collapsed');
  });
});

// ------------------------------------------------------------------
// 2) Búsqueda y filtrado
// ------------------------------------------------------------------
const searchInput = document.getElementById('searchInput');
const triggersList = document.getElementById('triggersList');
const clearAllFilters = document.getElementById('clearAllFilters');

// Colección de todos los checkboxes de filtros
const severityCheckboxes = document.querySelectorAll('#severityFilters input[type="checkbox"]');
const userCheckboxes = document.querySelectorAll('#userFilters input[type="checkbox"]');
const statusCheckboxes = document.querySelectorAll('#statusFilters input[type="checkbox"]');
const categoryCheckboxes = document.querySelectorAll('#categoryFilters input[type="checkbox"]');

// Eventos de búsqueda y checkboxes
searchInput.addEventListener('input', filtrarTriggers);
severityCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtrarTriggers));
userCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtrarTriggers));
statusCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtrarTriggers));
categoryCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtrarTriggers));

// Botón para limpiar todos los filtros
clearAllFilters.addEventListener('click', () => {
  // Limpiamos todos los checkboxes
  [...severityCheckboxes, ...userCheckboxes, ...statusCheckboxes, ...categoryCheckboxes]
    .forEach(checkbox => {
      checkbox.checked = false;
    });
  // Limpiamos el campo de búsqueda
  searchInput.value = '';
  // Volvemos a mostrar todo
  filtrarTriggers();
});

// Función principal de filtrado
function filtrarTriggers() {
  // Obtenemos los valores seleccionados de severidad
  const severitiesSeleccionadas = [...severityCheckboxes]
    .filter(checkbox => checkbox.checked)
    .map(checkbox => checkbox.getAttribute('data-severity'));

  // Obtenemos los valores seleccionados de usuario
  const usuariosSeleccionados = [...userCheckboxes]
    .filter(checkbox => checkbox.checked)
    .map(checkbox => checkbox.getAttribute('data-user'));

  // Obtenemos los valores seleccionados de status
  const statusSeleccionados = [...statusCheckboxes]
    .filter(checkbox => checkbox.checked)
    .map(checkbox => checkbox.getAttribute('data-status'));

  // Obtenemos los valores seleccionados de categoría
  const categoriasSeleccionadas = [...categoryCheckboxes]
    .filter(checkbox => checkbox.checked)
    .map(checkbox => checkbox.getAttribute('data-category'));

  // Término de búsqueda
  const terminoBusqueda = searchInput.value.toLowerCase().trim();

  // Recorremos cada trigger para mostrar/ocultar
  const triggerItems = triggersList.querySelectorAll('.trigger-item');
  triggerItems.forEach(trigger => {
    const triggerSeverity = trigger.getAttribute('data-severity');
    const triggerUser = trigger.getAttribute('data-user');
    const triggerStatus = trigger.getAttribute('data-status');
    const triggerCategory = trigger.getAttribute('data-category');
    const triggerTitle = (trigger.getAttribute('data-title') || '').toLowerCase();

    // 1) Búsqueda por título
    const coincideBusqueda = triggerTitle.includes(terminoBusqueda);

    // 2) Filtro de severidad
    let coincideSeverity = true;
    if (severitiesSeleccionadas.length > 0) {
      coincideSeverity = severitiesSeleccionadas.includes(triggerSeverity);
    }

    // 3) Filtro de usuario
    let coincideUser = true;
    if (usuariosSeleccionados.length > 0) {
      coincideUser = usuariosSeleccionados.includes(triggerUser);
    }

    // 4) Filtro de status
    let coincideStatus = true;
    if (statusSeleccionados.length > 0) {
      coincideStatus = statusSeleccionados.includes(triggerStatus);
    }

    // 5) Filtro de categoría
    let coincideCategory = true;
    if (categoriasSeleccionadas.length > 0) {
      coincideCategory = categoriasSeleccionadas.includes(triggerCategory);
    }

    // Decidimos si se muestra o no
    if (
      coincideBusqueda &&
      coincideSeverity &&
      coincideUser &&
      coincideStatus &&
      coincideCategory
    ) {
      trigger.style.display = 'flex';
    } else {
      trigger.style.display = 'none';
    }
  });
}

// Llamamos una primera vez para mostrar todo
filtrarTriggers();
 