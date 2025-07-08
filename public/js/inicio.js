  // Mapeo para actualizar los valores de las tarjetas inferiores según la opción elegida
  const bottomCardMapping = {
    'sites-dropdown': {
      'Sites': { 'Visualize': 1, 'Optimize': 1 },
      'Dispositivos': { 'Visualize': 1, 'Optimize': 2 }
    },
    'loggers-dropdown': {
      'Loggers': { 'Visualize': 0, 'Optimize': 0 },
      'Equipos': { 'Visualize': 1, 'Optimize': 3 }
    },
    'bridges-dropdown': {
      'Bridges': { 'Visualize': 0, 'Optimize': 40 },
      'Conexiones': { 'Visualize': 15, 'Optimize': 15 }
    },
    'sensors-dropdown': {
      'Sensors': { 'Visualize': 131, 'Optimize': 131 },
      'Power Meters': { 'Visualize': 6, 'Optimize': 6 }
    }
  };

  // Definición de estados alternativos para cada tarjeta
  const stateAlternatives = {
    'Sites': 'Dispositivos',
    'Dispositivos': 'Sites',
    'Loggers': 'Equipos',
    'Equipos': 'Loggers',
    'Bridges': 'Conexiones',
    'Conexiones': 'Bridges',
    'Sensors': 'Power Meters',
    'Power Meters': 'Sensors'
  };

  // Muestra u oculta el dropdown justo debajo de la flechita, generando dinámicamente la única opción alternativa
  function toggleDropdown(arrowElem, dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown.classList.contains('hidden')) {
      const card = arrowElem.closest('.card');
      const titleElement = card.querySelector('.card-title');
      const currentState = titleElement.textContent.trim();
      const alternative = stateAlternatives[currentState] || '';
      dropdown.innerHTML = '';
      if (alternative !== '') {
        const p = document.createElement('p');
        p.textContent = alternative;
        p.onclick = function() {
          selectOption(arrowElem, dropdownId, alternative);
        };
        dropdown.appendChild(p);
      }
      dropdown.classList.remove('hidden');
    } else {
      dropdown.classList.add('hidden');
    }
  }

  // Al seleccionar la opción, se actualiza el título del top card, sus datos y las tarjetas inferiores
  function selectOption(arrowElem, dropdownId, optionText) {
    const card = arrowElem.closest('.card');
    const titleElement = card.querySelector('.card-title');
    titleElement.textContent = optionText;
    
    // Actualiza los datos de cada top card según su dropdownId y la opción elegida
    if (dropdownId === 'sites-dropdown') {
      if (optionText === 'Sites') {
        card.querySelector('.count').textContent = '2';
      } else if (optionText === 'Dispositivos') {
        card.querySelector('.count').textContent = '3';
      }
    } else if (dropdownId === 'loggers-dropdown') {
      if (optionText === 'Loggers') {
        card.querySelector('.count').textContent = '0';
        card.querySelector('.offline').textContent = '0 Offline';
      } else if (optionText === 'Equipos') {
        card.querySelector('.count').textContent = '4';
        card.querySelector('.offline').textContent = '1 Offline';
      }
    } else if (dropdownId === 'bridges-dropdown') {
      if (optionText === 'Bridges') {
        card.querySelector('.count').textContent = '40';
        card.querySelector('.offline').textContent = '40 Offline';
      } else if (optionText === 'Conexiones') {
        card.querySelector('.count').textContent = '30';
        card.querySelector('.offline').textContent = '15 Offline';
      }
    } else if (dropdownId === 'sensors-dropdown') {
      if (optionText === 'Sensors') {
        card.querySelector('.count').textContent = '262';
        const extraElem = card.querySelector('.extra');
        if (extraElem) extraElem.textContent = '+12 Power Meters';
      } else if (optionText === 'Power Meters') {
        card.querySelector('.count').textContent = '12';
        const extraElem = card.querySelector('.extra');
        if (extraElem) extraElem.textContent = '';
      }
    }
    
    document.getElementById(dropdownId).classList.add('hidden');

    // Determina qué clave usar para actualizar la información en las tarjetas inferiores
    let filterKey = '';
    if (dropdownId.includes('sites')) {
      filterKey = 'site';
    } else if (dropdownId.includes('loggers')) {
      filterKey = 'loggers';
    } else if (dropdownId.includes('bridges')) {
      filterKey = 'bridges';
    } else if (dropdownId.includes('sensors')) {
      filterKey = 'sensors';
    }
    
    // Actualiza tanto las etiquetas como los valores en las tarjetas inferiores
    document.querySelectorAll('.site-card').forEach(card => {
      const cardType = card.getAttribute('data-site'); // "Visualize" o "Optimize"
      if (filterKey === 'site') {
        const labelElem = card.querySelector('.site-label');
        if (labelElem) labelElem.textContent = optionText + ':';
        const valueElem = card.querySelector('.site-value');
        if (bottomCardMapping['sites-dropdown'] && bottomCardMapping['sites-dropdown'][optionText] && valueElem) {
          valueElem.textContent = bottomCardMapping['sites-dropdown'][optionText][cardType];
        }
      } else if (filterKey === 'loggers') {
        const labelElem = card.querySelector('.loggers-label');
        if (labelElem) labelElem.textContent = optionText + ':';
        const valueElem = card.querySelector('.loggers-value');
        if (bottomCardMapping['loggers-dropdown'] && bottomCardMapping['loggers-dropdown'][optionText] && valueElem) {
          valueElem.textContent = bottomCardMapping['loggers-dropdown'][optionText][cardType];
        }
      } else if (filterKey === 'bridges') {
        const labelElem = card.querySelector('.bridges-label');
        if (labelElem) labelElem.textContent = optionText + ':';
        const valueElem = card.querySelector('.bridges-value');
        if (bottomCardMapping['bridges-dropdown'] && bottomCardMapping['bridges-dropdown'][optionText] && valueElem) {
          valueElem.textContent = bottomCardMapping['bridges-dropdown'][optionText][cardType];
        }
      } else if (filterKey === 'sensors') {
        const labelElem = card.querySelector('.sensors-label');
        if (labelElem) labelElem.textContent = optionText + ':';
        const valueElem = card.querySelector('.sensors-value');
        if (bottomCardMapping['sensors-dropdown'] && bottomCardMapping['sensors-dropdown'][optionText] && valueElem) {
          valueElem.textContent = bottomCardMapping['sensors-dropdown'][optionText][cardType];
        }
      }
    });
  }

  function filterCards() {
    const filter = document.getElementById("filterInput").value.toLowerCase();
    const cards = document.querySelectorAll(".site-card");
    cards.forEach(card => {
      const text = card.getAttribute("data-site").toLowerCase();
      card.style.display = text.includes(filter) ? "block" : "none";
    });
  }

  function changeView(view) {
    const container = document.querySelector('.cards-container');
    if (view === 'grid') {
      container.style.display = 'grid';
      container.style.gridTemplateColumns = 'repeat(2, 1fr)';
    } else if (view === 'list') {
      container.style.display = 'block';
    } else if (view === 'map') {
      alert('Map view is not implemented yet.');
    }
  }
 