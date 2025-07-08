document.addEventListener('DOMContentLoaded', function() {
  // Datos de ejemplo para las alertas
  const alertsData = [
    { id: 1, severity: 'high', status: 'open', title: 'Water Pumps are Idle or Off. Please check in person.', description: "Mains Water Pump 2's Power is below 13.0 kW", date: '2025-01-16', acknowledgedBy: 'User1' },
    { id: 2, severity: 'high', status: 'open', title: 'Water Pumps are Idle or Off. Please check in person.', description: "Mains Water Pump 3's Power is below 13.0 kW", date: '2025-01-27', acknowledgedBy: 'User2' },
    { id: 3, severity: 'medium', status: 'closed', title: 'Temperature Alert', description: 'Boiler temperature exceeded threshold', date: '2025-01-15', acknowledgedBy: 'User3' },
    { id: 4, severity: 'low', status: 'open', title: 'Pressure Drop', description: 'Minor pressure drop detected in Pump 1', date: '2025-01-20', acknowledgedBy: 'User4' },
    { id: 5, severity: 'high', status: 'closed', title: 'Critical Alert', description: 'Pump 5 has malfunctioned', date: '2025-01-22', acknowledgedBy: 'User5' },
    { id: 6, severity: 'medium', status: 'open', title: 'Routine Check', description: 'Pump 4 requires routine maintenance', date: '2025-01-18', acknowledgedBy: 'User6' },
    { id: 7, severity: 'high', status: 'open', title: 'Emergency Shutdown', description: 'Emergency shutdown activated for Pump 6', date: '2025-01-25', acknowledgedBy: 'User7' },
    { id: 8, severity: 'low', status: 'closed', title: 'Low Battery', description: 'Battery levels low on sensor A', date: '2025-01-19', acknowledgedBy: 'User8' },
    { id: 9, severity: 'medium', status: 'closed', title: 'Signal Loss', description: 'Lost communication with Pump 7', date: '2025-01-23', acknowledgedBy: 'User9' },
    { id: 10, severity: 'high', status: 'open', title: 'Water Pumps are Idle or Off. Please check in person.', description: "Mains Water Pump 8's Power is below 13.0 kW", date: '2025-01-28', acknowledgedBy: 'User10' },
    { id: 11, severity: 'low', status: 'open', title: 'Maintenance Required', description: 'Pump 9 scheduled for maintenance', date: '2025-01-21', acknowledgedBy: 'User11' }
  ];
  
  let currentFilter = 'active';
  let filteredAlerts = alertsData.slice();
  
  // Elementos del DOM
  const alertList = document.getElementById('alertList');
  const searchInput = document.getElementById('searchInput');
  const dateFilter = document.getElementById('dateFilter');
  const severityFilter = document.getElementById('severityFilter');
  const statusFilter = document.getElementById('statusFilter');
  const ackFilter = document.getElementById('ackFilter');
  const resetFiltersBtn = document.getElementById('resetFilters');
  const menuItems = document.querySelectorAll('.menu-item');
  
  // Función que aplica los filtros seleccionados
  function applyFilters() {
    filteredAlerts = alertsData.slice();
  
    // Filtrado según el menú (Active, Resolved, All)
    if (currentFilter === 'active') {
      filteredAlerts = filteredAlerts.filter(alert => alert.status === 'open');
    } else if (currentFilter === 'resolved') {
      filteredAlerts = filteredAlerts.filter(alert => alert.status === 'closed');
    }
  
    // Filtrado por búsqueda en título, descripción o usuario
    const searchText = searchInput.value.toLowerCase();
    if (searchText) {
      filteredAlerts = filteredAlerts.filter(alert =>
        alert.title.toLowerCase().includes(searchText) ||
        alert.description.toLowerCase().includes(searchText) ||
        alert.acknowledgedBy.toLowerCase().includes(searchText)
      );
    }
  
    // Filtrado por fecha
    const selectedDateFilter = dateFilter.value;
    if (selectedDateFilter !== 'all') {
      const now = new Date();
      filteredAlerts = filteredAlerts.filter(alert => {
        const alertDate = new Date(alert.date);
        if (selectedDateFilter === 'today') {
          return alertDate.toDateString() === now.toDateString();
        } else if (selectedDateFilter === 'week') {
          const diffTime = Math.abs(now - alertDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          return diffDays <= 7;
        } else if (selectedDateFilter === 'month') {
          return (alertDate.getMonth() === now.getMonth() && alertDate.getFullYear() === now.getFullYear());
        }
      });
    }
  
    // Filtrado por severidad
    const selectedSeverity = severityFilter.value;
    if (selectedSeverity !== 'all') {
      filteredAlerts = filteredAlerts.filter(alert => alert.severity === selectedSeverity);
    }
  
    // Filtrado por estado
    const selectedStatus = statusFilter.value;
    if (selectedStatus !== 'all') {
      filteredAlerts = filteredAlerts.filter(alert => alert.status === selectedStatus);
    }
  
    // Filtrado por usuario que reconoció la alerta
    const ackText = ackFilter.value.toLowerCase();
    if (ackText) {
      filteredAlerts = filteredAlerts.filter(alert => alert.acknowledgedBy.toLowerCase().includes(ackText));
    }
  
    renderAlerts();
    updateCounts();
  }
  
  // Función para renderizar la lista de alertas
  function renderAlerts() {
    alertList.innerHTML = '';
    if (filteredAlerts.length === 0) {
      alertList.innerHTML = '<p>No alerts found.</p>';
      return;
    }
    filteredAlerts.forEach(alert => {
      const alertDiv = document.createElement('div');
      alertDiv.classList.add('alert', alert.severity);
      alertDiv.classList.add('cardbg');
  
      // Contenedor para los badges (severidad y estado)
      const badgeContainer = document.createElement('div');
      badgeContainer.classList.add('badge-container');
  
      // Badge de severidad
      const severityBadge = document.createElement('span');
      severityBadge.classList.add('badge', alert.severity);
      severityBadge.textContent = alert.severity;
  
      // Badge de status
      const statusBadge = document.createElement('span');
      statusBadge.classList.add('badge', alert.status);
      statusBadge.textContent = alert.status;
  
      badgeContainer.appendChild(severityBadge);
      badgeContainer.appendChild(statusBadge);
  
      // Título y descripción
      const title = document.createElement('h4');
      title.textContent = alert.title;
      const description = document.createElement('p');
      description.textContent = alert.description;
  
      // Fecha formateada
      const dateSpan = document.createElement('span');
      dateSpan.classList.add('date');
      dateSpan.textContent = formatDate(alert.date);
  
      alertDiv.appendChild(badgeContainer);
      alertDiv.appendChild(title);
      alertDiv.appendChild(description);
      alertDiv.appendChild(dateSpan);
  
      alertList.appendChild(alertDiv);
    });
  }
  
  // Actualiza los contadores del menú
  function updateCounts() {
    const activeCount = alertsData.filter(alert => alert.status === 'open').length;
    const resolvedCount = alertsData.filter(alert => alert.status === 'closed').length;
    const allCount = alertsData.length;
  
    document.querySelector('.menu-item[data-filter="active"] .count').textContent = activeCount;
    document.querySelector('.menu-item[data-filter="resolved"] .count').textContent = resolvedCount;
    document.querySelector('.menu-item[data-filter="all"] .count').textContent = allCount;
  }
  
  // Formatea la fecha de manera legible
  function formatDate(dateStr) {
    const dateObj = new Date(dateStr);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return dateObj.toLocaleDateString('en-US', options);
  }
  
  // Eventos para los filtros y búsqueda
  menuItems.forEach(item => {
    item.addEventListener('click', function() {
      menuItems.forEach(mi => mi.classList.remove('active'));
      this.classList.add('active');
      currentFilter = this.getAttribute('data-filter');
      applyFilters();
    });
  });
  
  searchInput.addEventListener('input', applyFilters);
  dateFilter.addEventListener('change', applyFilters);
  severityFilter.addEventListener('change', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
  ackFilter.addEventListener('input', applyFilters);
  
  // Botón para resetear filtros
  resetFiltersBtn.addEventListener('click', function() {
    searchInput.value = '';
    dateFilter.value = 'all';
    severityFilter.value = 'all';
    statusFilter.value = 'all';
    ackFilter.value = '';
    currentFilter = 'active';
    menuItems.forEach(mi => mi.classList.remove('active'));
    document.querySelector('.menu-item[data-filter="active"]').classList.add('active');
    applyFilters();
  });
  
  // Inicializa la vista
  applyFilters();
});
 