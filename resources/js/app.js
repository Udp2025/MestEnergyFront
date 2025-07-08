import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
import { createApp } from 'vue';
import DashboardEditor from './components/DashboardEditor.vue';

// Crea la instancia de Vue
const app = createApp({});

// Registra el componente globalmente (para usarlo en Blade con <dashboard-editor>)
app.component('dashboard-editor', DashboardEditor);

// Monta la app en el elemento #app (que estará en tu Blade)
app.mount('#app');
