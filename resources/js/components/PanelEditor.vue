<template>
  <div class="panel-editor">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2>{{ panel.title }}</h2>
      <button class="btn btn-primary" @click="openAddWidgetModal">
        + Add Widget
      </button>
    </div>

    <!-- Editar título del panel -->
    <div class="mb-3">
      <label for="title">Título del Panel</label>
      <input
        type="text"
        v-model="panelTitle"
        class="form-control"
        @change="updatePanelTitle"
      />
    </div>

    <!-- Área de Gridstack -->
    <div ref="gridContainer" class="grid-stack"></div>

    <!-- Modal para Agregar Widget -->
    <add-widget-modal
      v-if="showAddWidgetModal"
      @close="showAddWidgetModal = false"
      @widget-added="addWidget"
    ></add-widget-modal>

    <!-- Modal para Preferencias de Widget -->
    <widget-preferences-modal
      v-if="selectedWidget"
      :widget="selectedWidget"
      @close="selectedWidget = null"
      @save="saveWidgetPreferences"
    ></widget-preferences-modal>
  </div>
</template>

<script>
import { onMounted, ref, reactive } from 'vue';
import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

import AddWidgetModal from './AddWidgetModal.vue';
import WidgetPreferencesModal from './WidgetPreferencesModal.vue';
import axios from 'axios';

export default {
  name: 'PanelEditor',
  components: {
    AddWidgetModal,
    WidgetPreferencesModal
  },
  props: {
    panel: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const gridContainer = ref(null);
    let grid = null;

    // Datos reactivos del panel
    const panelData = reactive({
      id: props.panel.id,
      title: props.panel.title,
      widgets: props.panel.widgets || []
    });

    // Para el input de título
    const panelTitle = ref(panelData.title);

    // Control de modales
    const showAddWidgetModal = ref(false);
    const selectedWidget = ref(null);

    // Inicializar GridStack en el montaje
    onMounted(() => {
      grid = GridStack.init({
        cellHeight: 150,
        float: false,
        resizable: { handles: 'all' },
        draggable: {
          handle: '.grid-stack-item-content'
        }
      }, gridContainer.value);

      // Cargar widgets existentes
      panelData.widgets.forEach((w) => {
        addWidgetToGrid(w);
      });

      // Evento 'change': se activa cuando se mueve o redimensiona un widget
      grid.on('change', function(e, items) {
        items.forEach((item) => {
          const widgetId = item.el.getAttribute('data-widget-id');
          const found = panelData.widgets.find((w) => w.id == widgetId);
          if (found) {
            found.config.x = item.x;
            found.config.y = item.y;
            found.config.w = item.w;
            found.config.h = item.h;
          }
        });
      });
    });

    // Abrir modal de "Add Widget"
    const openAddWidgetModal = () => {
      showAddWidgetModal.value = true;
    };

    // Agregar widget desde el modal
    const addWidget = (widgetData) => {
      axios.post(`/panels/${panelData.id}/widgets`, widgetData)
        .then(response => {
          const newWidget = response.data.graph; // devuelto por el servidor
          panelData.widgets.push(newWidget);
          addWidgetToGrid(newWidget);
        })
        .catch(err => console.error(err));
    };

    // Pintar el widget en el grid
    const addWidgetToGrid = (widget) => {
      const x = widget.config?.x || 0;
      const y = widget.config?.y || 0;
      const w = widget.config?.w || 4;
      const h = widget.config?.h || 2;

      const gridItem = document.createElement('div');
      gridItem.classList.add('grid-stack-item');
      gridItem.setAttribute('data-widget-id', widget.id);
      gridItem.setAttribute('gs-x', x);
      gridItem.setAttribute('gs-y', y);
      gridItem.setAttribute('gs-w', w);
      gridItem.setAttribute('gs-h', h);

      const content = document.createElement('div');
      content.classList.add('grid-stack-item-content');
      content.style.background = '#f8f9fa';
      content.style.border = '1px solid #ddd';
      content.style.position = 'relative';
      content.innerHTML = `
        <div style="padding:10px;">
          <strong>${widget.name}</strong>
          <br/>
          <small>Tipo: ${widget.type}</small>
        </div>
        <button class="btn btn-sm btn-secondary" style="position:absolute; bottom:5px; right:5px;">
          Configurar
        </button>
      `;

      // Al hacer clic en "Configurar"
      content.querySelector('button').addEventListener('click', () => {
        selectedWidget.value = widget;
      });

      gridItem.appendChild(content);
      grid.addWidget(gridItem);
    };

    // Actualizar título del panel
    const updatePanelTitle = () => {
      axios.put(`/panels/${panelData.id}`, {
        title: panelTitle.value
      })
      .then(() => {
        panelData.title = panelTitle.value;
      })
      .catch(err => console.error(err));
    };

    // Guardar preferencias del widget
    const saveWidgetPreferences = (updatedWidget) => {
      axios.put(`/panels/${panelData.id}/widgets/${updatedWidget.id}`, updatedWidget)
        .then(response => {
          const index = panelData.widgets.findIndex(w => w.id === updatedWidget.id);
          if (index !== -1) {
            panelData.widgets[index] = response.data.graph;
          }
          selectedWidget.value = null; // cierra el modal
        })
        .catch(err => console.error(err));
    };

    return {
      gridContainer,
      panelData,
      panelTitle,
      showAddWidgetModal,
      selectedWidget,
      openAddWidgetModal,
      addWidget,
      updatePanelTitle,
      saveWidgetPreferences
    };
  }
};
</script>

<style scoped>
.panel-editor {
  min-height: 80vh;
}
.grid-stack {
  background: #fff;
  border: 1px solid #ccc;
  min-height: 500px;
  margin-bottom: 2rem;
}
</style>
