<template>
    <div class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h5>Preferencias de Widget</h5>
          <button @click="$emit('close')" class="btn-close">x</button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label>Nombre</label>
            <input type="text" v-model="localWidget.name" class="form-control" />
          </div>
          <div class="mb-2">
            <label>Tipo de gráfica</label>
            <select v-model="localWidget.type" class="form-control">
              <option value="line">Línea</option>
              <option value="bar">Barras</option>
            </select>
          </div>
          <div class="mb-2">
            <label>Datos (array)</label>
            <input
              type="text"
              v-model="dataString"
              class="form-control"
              placeholder="Ej: 10,20,30"
            />
          </div>
          <div class="mb-2">
            <label>Etiquetas (array)</label>
            <input
              type="text"
              v-model="labelsString"
              class="form-control"
              placeholder="Ej: Ene,Feb,Mar"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="saveChanges">Guardar</button>
          <button class="btn btn-secondary" @click="$emit('close')">Cerrar</button>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'WidgetPreferencesModal',
    props: {
      widget: {
        type: Object,
        required: true
      }
    },
    data() {
      return {
        // Clonamos el widget para no mutar directamente la prop
        localWidget: JSON.parse(JSON.stringify(this.widget))
      };
    },
    computed: {
      dataString: {
        get() {
          return this.localWidget.config.dataset.join(',');
        },
        set(val) {
          this.localWidget.config.dataset = val.split(',').map(num => parseFloat(num));
        }
      },
      labelsString: {
        get() {
          return this.localWidget.config.labels.join(',');
        },
        set(val) {
          this.localWidget.config.labels = val.split(',');
        }
      }
    },
    methods: {
      saveChanges() {
        // Emitimos el widget actualizado al padre
        this.$emit('save', this.localWidget);
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
    top: 15%;
    left: 50%;
    width: 400px;
    transform: translateX(-50%);
    background: #fff;
    border-radius: 4px;
    padding: 1rem;
  }
  .btn-close {
    background: transparent;
    border: none;
    font-size: 1.2rem;
    margin-left: auto;
    cursor: pointer;
  }
  </style>
  