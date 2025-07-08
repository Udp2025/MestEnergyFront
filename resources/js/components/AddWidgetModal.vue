<template>
    <div class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h5>Agregar Nuevo Widget</h5>
          <button @click="$emit('close')" class="btn-close">x</button>
        </div>
        <div class="modal-body">
          <div class="widget-list">
            <div
              v-for="(widget, i) in availableWidgets"
              :key="i"
              class="widget-item"
              @click="selectWidget(widget)"
            >
              <h6>{{ widget.name }}</h6>
              <p>Tipo: {{ widget.type }}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="$emit('close')">Cerrar</button>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'AddWidgetModal',
    data() {
      return {
        // Lista de widgets "plantilla"
        availableWidgets: [
          {
            name: 'Gráfica de Luz (Línea)',
            type: 'line',
            config: {
              x: 0, y: 0, w: 4, h: 2,
              dataset: [10, 20, 30, 25, 15, 40],
              labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']
            }
          },
          {
            name: 'Gráfica de Temperatura (Barras)',
            type: 'bar',
            config: {
              x: 0, y: 0, w: 4, h: 2,
              dataset: [5, 10, 8, 12, 15, 9],
              labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']
            }
          },
          {
            name: 'Lectura de Voltaje (Línea)',
            type: 'line',
            config: {
              x: 0, y: 0, w: 4, h: 2,
              dataset: [220, 221, 219, 222, 218, 223],
              labels: ['10:00', '10:05', '10:10', '10:15', '10:20', '10:25']
            }
          }
        ]
      };
    },
    methods: {
      selectWidget(widget) {
        this.$emit('widget-added', {
          name: widget.name,
          type: widget.type,
          config: widget.config
        });
        this.$emit('close');
      }
    }
  };
  </script>
  
  <style scoped>
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
  }
  .modal-content {
    position: absolute;
    top: 10%;
    left: 50%;
    width: 400px;
    transform: translateX(-50%);
    background: #fff;
    border-radius: 4px;
    padding: 1rem;
  }
  .widget-list {
    display: flex;
    flex-direction: column;
  }
  .widget-item {
    border: 1px solid #ddd;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
  }
  .btn-close {
    background: transparent;
    border: none;
    font-size: 1.2rem;
    margin-left: auto;
    cursor: pointer;
  }
  </style>
  