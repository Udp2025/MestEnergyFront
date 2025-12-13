/***************************************
 * Datos base de ejemplo (más variados)
 *
 * Para cada sitio y período se definen estructuras con mayor cantidad
 * de nodos y enlaces, de modo que el diagrama muestre múltiples flujos.
 ***************************************/
const baseData = {
  textiles: {
    daily: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "PV/Solar" },
        { node: 4, name: "Machinery" },
        { node: 5, name: "HVAC" },
        { node: 6, name: "Lighting" },
        { node: 7, name: "Water Pumps" },
        { node: 8, name: "Boilers" }
      ],
      links: [
        { source: 0, target: 4, value: 100 },
        { source: 1, target: 4, value: 50 },
        { source: 2, target: 5, value: 70 },
        { source: 3, target: 6, value: 60 },
        { source: 0, target: 7, value: 80 },
        { source: 1, target: 8, value: 40 },
        { source: 3, target: 7, value: 45 }
      ]
    },
    weekly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "PV/Solar" },
        { node: 4, name: "Machinery" },
        { node: 5, name: "HVAC" },
        { node: 6, name: "Lighting" },
        { node: 7, name: "Water Pumps" },
        { node: 8, name: "Boilers" }
      ],
      links: [
        { source: 0, target: 4, value: 700 },
        { source: 1, target: 4, value: 350 },
        { source: 2, target: 5, value: 490 },
        { source: 3, target: 6, value: 420 },
        { source: 0, target: 7, value: 560 },
        { source: 1, target: 8, value: 280 },
        { source: 3, target: 7, value: 315 }
      ]
    },
    monthly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "PV/Solar" },
        { node: 4, name: "Machinery" },
        { node: 5, name: "HVAC" },
        { node: 6, name: "Lighting" },
        { node: 7, name: "Water Pumps" },
        { node: 8, name: "Boilers" }
      ],
      links: [
        { source: 0, target: 4, value: 3000 },
        { source: 1, target: 4, value: 1500 },
        { source: 2, target: 5, value: 2100 },
        { source: 3, target: 6, value: 1800 },
        { source: 0, target: 7, value: 2400 },
        { source: 1, target: 8, value: 1200 },
        { source: 3, target: 7, value: 1350 }
      ]
    },
    yearly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "PV/Solar" },
        { node: 4, name: "Machinery" },
        { node: 5, name: "HVAC" },
        { node: 6, name: "Lighting" },
        { node: 7, name: "Water Pumps" },
        { node: 8, name: "Boilers" }
      ],
      links: [
        { source: 0, target: 4, value: 36500 },
        { source: 1, target: 4, value: 18250 },
        { source: 2, target: 5, value: 25550 },
        { source: 3, target: 6, value: 21900 },
        { source: 0, target: 7, value: 29200 },
        { source: 1, target: 8, value: 14600 },
        { source: 3, target: 7, value: 16425 }
      ]
    }
  },
  food: {
    daily: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "PV/Solar" },
        { node: 2, name: "Machinery" },
        { node: 3, name: "HVAC" },
        { node: 4, name: "Refrigeration" },
        { node: 5, name: "Lighting" }
      ],
      links: [
        { source: 0, target: 2, value: 80 },
        { source: 1, target: 3, value: 40 },
        { source: 0, target: 4, value: 60 },
        { source: 1, target: 5, value: 30 },
        { source: 0, target: 2, value: 20 }
      ]
    },
    weekly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "PV/Solar" },
        { node: 2, name: "Machinery" },
        { node: 3, name: "HVAC" },
        { node: 4, name: "Refrigeration" },
        { node: 5, name: "Lighting" }
      ],
      links: [
        { source: 0, target: 2, value: 560 },
        { source: 1, target: 3, value: 280 },
        { source: 0, target: 4, value: 420 },
        { source: 1, target: 5, value: 210 },
        { source: 0, target: 2, value: 140 }
      ]
    },
    monthly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "PV/Solar" },
        { node: 2, name: "Machinery" },
        { node: 3, name: "HVAC" },
        { node: 4, name: "Refrigeration" },
        { node: 5, name: "Lighting" }
      ],
      links: [
        { source: 0, target: 2, value: 2400 },
        { source: 1, target: 3, value: 1200 },
        { source: 0, target: 4, value: 1800 },
        { source: 1, target: 5, value: 900 },
        { source: 0, target: 2, value: 600 }
      ]
    },
    yearly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "PV/Solar" },
        { node: 2, name: "Machinery" },
        { node: 3, name: "HVAC" },
        { node: 4, name: "Refrigeration" },
        { node: 5, name: "Lighting" }
      ],
      links: [
        { source: 0, target: 2, value: 29200 },
        { source: 1, target: 3, value: 14600 },
        { source: 0, target: 4, value: 21900 },
        { source: 1, target: 5, value: 10950 },
        { source: 0, target: 2, value: 7300 }
      ]
    }
  },
  chemical: {
    daily: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "Machinery" },
        { node: 4, name: "HVAC" },
        { node: 5, name: "Lighting" },
        { node: 6, name: "Boilers" },
        { node: 7, name: "Cooling Tower" }
      ],
      links: [
        { source: 0, target: 3, value: 150 },
        { source: 1, target: 4, value: 100 },
        { source: 2, target: 5, value: 80 },
        { source: 0, target: 6, value: 60 },
        { source: 1, target: 7, value: 50 },
        { source: 2, target: 4, value: 40 }
      ]
    },
    weekly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "Machinery" },
        { node: 4, name: "HVAC" },
        { node: 5, name: "Lighting" },
        { node: 6, name: "Boilers" },
        { node: 7, name: "Cooling Tower" }
      ],
      links: [
        { source: 0, target: 3, value: 1050 },
        { source: 1, target: 4, value: 700 },
        { source: 2, target: 5, value: 560 },
        { source: 0, target: 6, value: 420 },
        { source: 1, target: 7, value: 350 },
        { source: 2, target: 4, value: 280 }
      ]
    },
    monthly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "Machinery" },
        { node: 4, name: "HVAC" },
        { node: 5, name: "Lighting" },
        { node: 6, name: "Boilers" },
        { node: 7, name: "Cooling Tower" }
      ],
      links: [
        { source: 0, target: 3, value: 4500 },
        { source: 1, target: 4, value: 3000 },
        { source: 2, target: 5, value: 2400 },
        { source: 0, target: 6, value: 1800 },
        { source: 1, target: 7, value: 1500 },
        { source: 2, target: 4, value: 1200 }
      ]
    },
    yearly: {
      nodes: [
        { node: 0, name: "Grid" },
        { node: 1, name: "Generator" },
        { node: 2, name: "Fuel Cell" },
        { node: 3, name: "Machinery" },
        { node: 4, name: "HVAC" },
        { node: 5, name: "Lighting" },
        { node: 6, name: "Boilers" },
        { node: 7, name: "Cooling Tower" }
      ],
      links: [
        { source: 0, target: 3, value: 54750 },
        { source: 1, target: 4, value: 36500 },
        { source: 2, target: 5, value: 29200 },
        { source: 0, target: 6, value: 21900 },
        { source: 1, target: 7, value: 18250 },
        { source: 2, target: 4, value: 14600 }
      ]
    }
  }
};

/***************************************
 * Función: applyDateModifier
 * Simula la modificación de los datos base según la fecha seleccionada.
 * En este ejemplo se usa el día del mes para calcular un factor entre 0.8 y 1.3.
 ***************************************/
function applyDateModifier(data, dateStr) {
  if (!dateStr) return data;
  const selectedDate = new Date(dateStr);
  const factor = 0.8 + (selectedDate.getDate() / 31) * 0.5;
  return {
    nodes: data.nodes.map(d => ({ ...d })),
    links: data.links.map(link => ({ ...link, value: Math.round(link.value * factor) }))
  };
}

/***************************************
 * Función: updateSankey
 * Lee los filtros (sitio, período y fecha), selecciona y modifica los datos,
 * y redibuja el diagrama Sankey.
 ***************************************/
function updateSankey() {
  // Recoger filtros
  const site = document.getElementById("site-select").value;
  const period = document.getElementById("period-select").value;
  const dateVal = document.getElementById("date-filter").value;
  
  // Seleccionar datos base según sitio y período (si no existe, usar "daily")
  let data = (baseData[site] && baseData[site][period]) || baseData[site]["daily"];
  // Aplicar modificador según la fecha (si se ha seleccionado)
  data = applyDateModifier(data, dateVal);
  
  // Actualizar la fecha en el pie de página (si se seleccionó)
  if (dateVal) {
    const selectedDate = new Date(dateVal);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById("datetime").textContent = selectedDate.toLocaleDateString("es-ES", options);
  }
  
  // Seleccionar y limpiar el SVG
  const svg = d3.select("#sankey");
  svg.selectAll("*").remove();
  const width = +svg.attr("width");
  const height = +svg.attr("height");
  
  /***************************************
   * Definición de filtros y gradientes en SVG
   ***************************************/
  const defs = svg.append("defs");
  // Filtro drop-shadow para nodos
  const filter = defs.append("filter")
                     .attr("id", "drop-shadow")
                     .attr("height", "130%");
  filter.append("feGaussianBlur")
        .attr("in", "SourceAlpha")
        .attr("stdDeviation", 2)
        .attr("result", "blur");
  filter.append("feOffset")
        .attr("in", "blur")
        .attr("dx", 2)
        .attr("dy", 2)
        .attr("result", "offsetBlur");
  const feMerge = filter.append("feMerge");
  feMerge.append("feMergeNode").attr("in", "offsetBlur");
  feMerge.append("feMergeNode").attr("in", "SourceGraphic");
  
  // Gradiente para los enlaces
  const gradient = defs.append("linearGradient")
                       .attr("id", "link-gradient")
                       .attr("x1", "0%")
                       .attr("y1", "0%")
                       .attr("x2", "100%")
                       .attr("y2", "0%");
  gradient.append("stop")
          .attr("offset", "0%")
          .attr("stop-color", "#ffa726");
  gradient.append("stop")
          .attr("offset", "100%")
          .attr("stop-color", "#fb8c00");
  
  /***************************************
   * Configuración del generador Sankey
   ***************************************/
  // Se reduce el ancho de los nodos y se ajusta el padding para obtener líneas más finas
  const sankeyGenerator = d3.sankey()
                            .nodeWidth(10)
                            .nodePadding(10)
                            .extent([[1, 1], [width - 1, height - 6]]);
  const graph = sankeyGenerator({
    nodes: data.nodes.map(d => Object.assign({}, d)),
    links: data.links.map(d => Object.assign({}, d))
  });
  
  // Seleccionar el tooltip
  const tooltip = d3.select("#tooltip");
  
  /***************************************
   * Dibujado de los enlaces
   ***************************************/
  svg.append("g")
     .selectAll("path")
     .data(graph.links)
     .enter()
     .append("path")
     .attr("d", d3.sankeyLinkHorizontal())
     // Se reduce el grosor de las líneas multiplicando d.width por 0.3
     .attr("stroke-width", d => Math.max(1, d.width * 0.3))
     .attr("fill", "none")
     .attr("stroke", "url(#link-gradient)")
     .attr("opacity", 0.7)
     .on("mouseover", function(event, d) {
        tooltip.style("opacity", 1)
               .html(`<strong>${d.source.name} → ${d.target.name}</strong><br/>Valor: ${d.value}`);
     })
     .on("mousemove", function(event) {
        // Posicionar el tooltip a 15px a la derecha y 10px arriba del cursor
        tooltip.style("left", (event.pageX + 15) + "px")
               .style("top", (event.pageY - 10) + "px");
     })
     .on("mouseout", function() {
        tooltip.style("opacity", 0);
     });
  
  /***************************************
   * Dibujado de los nodos
   ***************************************/
  svg.append("g")
     .selectAll("rect")
     .data(graph.nodes)
     .enter()
     .append("rect")
     .attr("class", "node")
     .attr("x", d => d.x0)
     .attr("y", d => d.y0)
     .attr("height", d => d.y1 - d.y0)
     .attr("width", d => d.x1 - d.x0)
     .attr("fill", d => d.index < data.nodes.length / 2 ? "#66bb6a" : "#42a5f5")
     .attr("stroke", "#333")
     .on("mouseover", function(event, d) {
        tooltip.style("opacity", 1)
               .html(`<strong>${d.name}</strong>`);
     })
     .on("mousemove", function(event) {
        tooltip.style("left", (event.pageX + 15) + "px")
               .style("top", (event.pageY - 10) + "px");
     })
     .on("mouseout", function() {
        tooltip.style("opacity", 0);
     });
  
  /***************************************
   * Etiquetas de nodos
   ***************************************/
  svg.append("g")
     .selectAll("text")
     .data(graph.nodes)
     .enter()
     .append("text")
     .attr("x", d => d.x0 < width / 2 ? d.x1 + 6 : d.x0 - 6)
     .attr("y", d => (d.y1 + d.y0) / 2)
     .attr("dy", "0.35em")
     .attr("text-anchor", d => d.x0 < width / 2 ? "start" : "end")
     .text(d => d.name)
     .attr("font-size", "13px")
     .attr("fill", "#555");
}

/***************************************
 * Eventos: actualizar el gráfico al cambiar filtros
 ***************************************/
document.getElementById("site-select").addEventListener("change", updateSankey);
document.getElementById("period-select").addEventListener("change", updateSankey);
document.getElementById("date-filter").addEventListener("change", updateSankey);

// Primera carga
updateSankey();
 

document.addEventListener('DOMContentLoaded', function() {
  // Selecciona todos los selects con id="site-select" dentro de la sidebar
  // (aunque el id esté duplicado, querySelectorAll devuelve todos)
  const selects = document.querySelectorAll('.sidebar select#site-select');

  selects.forEach(function(sel) {
    // crear wrapper y display sin tocar el id del select
    const wrapper = document.createElement('div');
    wrapper.className = 'select-wrap';

    const display = document.createElement('div');
    display.className = 'select-display';
    display.setAttribute('aria-hidden', 'true');

    // insertar wrapper en el DOM en la posición del select
    sel.parentNode.insertBefore(wrapper, sel);
    // mover el select dentro del wrapper
    wrapper.appendChild(sel);
    // insertar el display DESPUÉS del select (así usamos select:focus + .select-display)
    wrapper.appendChild(display);

    // función para actualizar el texto visible
    const update = () => {
      const opt = sel.options[sel.selectedIndex];
      display.textContent = opt ? opt.text : '';
      sel.setAttribute('title', opt ? opt.text : '');
      display.setAttribute('aria-label', opt ? opt.text : '');
    };

    // inicializa
    update();

    // actualizar cuando cambie
    sel.addEventListener('change', update);
    sel.addEventListener('input', update);

    // también actualizar si el select cambia por JS
    const observer = new MutationObserver(update);
    observer.observe(sel, { childList: true, subtree: true, characterData: true });
  });
});
