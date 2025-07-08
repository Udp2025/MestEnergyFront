// Alternar Sidebar
const toggleBtn = document.getElementById('toggle-btn');
const sidebar = document.getElementById('sidebar');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('minimized');
});

// Actualizar hora
function updateTime() {
    const now = new Date();
    document.getElementById('time').textContent = now.toLocaleTimeString();
}
setInterval(updateTime, 1000);

// Función para cerrar sesión
function logout() {
    alert('Sesión cerrada');
}
