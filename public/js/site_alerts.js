document.addEventListener('DOMContentLoaded', function() {
  // Datos de ejemplo para las alertas (ajustados a la imagen)
  const alertsData = [
    { 
      id: 1, 
      empresa: 'Mest Manufacturing April 2024',
      fecha: '2024-04-15',
      severidad: 'critical', 
      estado: 'active', 
      responsable: 'Vanessa Nest',
      acknowledgedBy: 'Admin'
    },
    { 
      id: 2, 
      empresa: 'Mest Manufacturing April 2024',
      fecha: '2024-04-14',
      severidad: 'high', 
      estado: 'active', 
      responsable: 'Robert Brown',
      acknowledgedBy: 'Tech'
    },
    { 
      id: 3, 
      empresa: 'Mest Manufacturing April 2024',
      fecha: '2024-04-16',
      severidad: 'critical', 
      estado: 'active', 
      responsable: 'Quinada Fallk',
      acknowledgedBy: 'Manager'
    },
    { 
      id: 4, 
      empresa: 'Mest Manufacturing April 2024',
      fecha: '2024-04-12',
      severidad: 'critical', 
      estado: 'active', 
      responsable: 'Octavio Kest',
      acknowledgedBy: 'Supervisor'
    },
    { 
      id: 5, 
      empresa: 'Mest Manufacturing April 2024',
      fecha: '2024-04-11',
      severidad: 'critical', 
      estado: 'active', 
      responsable: 'Vanessa Gest',
      acknowledgedBy: 'Operator'
    }
  ];
  
  let currentFilter = 'active';
  let filteredAlerts = alertsData.slice();
  
  // Elementos del DOM
  const alertList = document.getElementById('alertList');
  const searchInput = document.getElementById('searchInput');
  const menuItems = document.querySelectorAll('.menu-item');
  const dateFilter = document.getElementById('dateFilter');
  const severityFilter = document.getElementById('severityFilter');
  const statusFilter = document.getElementById('statusFilter');
  const ackFilter = document.getElementById('ackFilter');
  const resetFiltersBtn = document.getElementById('resetFilters');
  
  // Función que aplica los filtros seleccionados
  function applyFilters() {
    filteredAlerts = alertsData.slice();
    
    // Filtrado según el menú (Active, Resolved, All)
    if (currentFilter === 'active') {
      filteredAlerts = filteredAlerts.filter(alert => alert.estado === 'active');
    } else if (currentFilter === 'resolved') {
      filteredAlerts = filteredAlerts.filter(alert => alert.estado === 'resolved');
    }
    
    // Filtrado por búsqueda
    const searchText = searchInput.value.toLowerCase();
    if (searchText) {
      filteredAlerts = filteredAlerts.filter(alert =>
        alert.empresa.toLowerCase().includes(searchText) ||
        alert.responsable.toLowerCase().includes(searchText) ||
        alert.acknowledgedBy.toLowerCase().includes(searchText)
      );
    }
    
    // Filtrado por fecha
    const selectedDate = dateFilter.value;
    if (selectedDate !== 'all') {
      const now = new Date();
      filteredAlerts = filteredAlerts.filter(alert => {
        const alertDate = new Date(alert.fecha);
        if (selectedDate === 'today') {
          return alertDate.toDateString() === now.toDateString();
        } else if (selectedDate === 'week') {
          const diffTime = Math.abs(now - alertDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          return diffDays <= 7;
        } else if (selectedDate === 'month') {
          return (alertDate.getMonth() === now.getMonth() && alertDate.getFullYear() === now.getFullYear());
        }
      });
    }
    
    // Filtrado por severidad
    const selectedSeverity = severityFilter.value;
    if (selectedSeverity !== 'all') {
      filteredAlerts = filteredAlerts.filter(alert => alert.severidad === selectedSeverity);
    }
    
    // Filtrado por estado
    const selectedStatus = statusFilter.value;
    if (selectedStatus !== 'all') {
      filteredAlerts = filteredAlerts.filter(alert => alert.estado === selectedStatus);
    }
    
    // Filtrado por usuario que reconoció la alerta
    const ackText = ackFilter.value.toLowerCase();
    if (ackText) {
      filteredAlerts = filteredAlerts.filter(alert => alert.acknowledgedBy.toLowerCase().includes(ackText));
    }
    
    renderAlerts();
  }
  
  // Función para renderizar la lista de alertas
  function renderAlerts() {
    alertList.innerHTML = '';
    
    if (filteredAlerts.length === 0) {
      alertList.innerHTML = '<tr><td colspan="5">No alerts found.</td></tr>';
      return;
    }
    
    filteredAlerts.forEach(alert => {
      const row = document.createElement('tr');
      
      // Formatear la fecha
      const formattedDate = formatDate(alert.fecha);
      
      row.innerHTML = `
        <td>${alert.empresa}</td>
        <td>${formattedDate}</td>
        <td><span class="badge ${alert.severidad}">${alert.severidad}</span></td>
        <td><span class="badge ${alert.estado}">${alert.estado}</span></td>
        <td>${alert.responsable}</td>
      `;
      
      alertList.appendChild(row);
    });
  }
  
  // Formatea la fecha de manera legible
  function formatDate(dateStr) {
    const dateObj = new Date(dateStr);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
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