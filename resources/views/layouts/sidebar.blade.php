<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar</title>
  <!-- Estilos generales y específicos -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <!-- Fuentes -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Iconos -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    /* Aquí puedes incluir estilos específicos para el sidebar si los requieres */
    /* Por ejemplo, para asegurar que el sidebar se muestre correctamente en esta vista independiente */
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      background: #ecf0f1;
    }
    .custom-sidebar {
      width: 250px;
      background-color: #ffffff;
      color: #080808;
      display: flex;
      flex-direction: column;
      transition: all 0.3s;
      position: relative;
      min-height: 100vh;
    }
    .custom-sidebar.collapsed {
      width: 70px;
    }
    .custom-logo-section {
      display: flex;
      align-items: center;
      padding: 10px 20px;
      background-color: #ffffff;
      position: relative;
    }
    .custom-logo-section img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
    }
    .custom-logo-text {
      font-size: 18px;
      margin-left: 10px;
      transition: opacity 0.3s;
    }
    .custom-sidebar.collapsed .custom-logo-text {
      display: none;
    }
    .custom-toggle-btn {
      background: none;
      border: none;
      color: #000;
      font-size: 18px;
      cursor: pointer;
      position: absolute;
      right: 20px;
    }
    .custom-menu-section {
      padding: 1rem;
    }
    .custom-menu-title {
      font-size: 12px;
      text-transform: uppercase;
      margin: 15px 0 5px 15px;
      color: #000;
    }
    .custom-sidebar ul {
      list-style: none;
      padding: 0;
    }
    .custom-sidebar ul li {
      padding: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 14px;
    }
    .custom-sidebar ul li i {
      font-size: 16px;
    }
    .custom-sidebar.collapsed ul li {
      justify-content: center;
    }
    .custom-sidebar.collapsed ul li span {
      display: none;
    }
    .custom-sidebar ul li:hover {
      background-color: #F68D2E;
    }
    .custom-logout {
      margin-top: auto;
      padding: 30px;
    }
    .custom-logout a {
      width: 65%;
      padding: 2px;
      color: #050505;
      border: none;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      text-align: left;
      font-size: 15px;
    }
    .custom-sidebar.collapsed .custom-logout a {
      justify-content: center;
    }
  </style>
</head>
<body>
  <div class="custom-sidebar" id="sidebar">
    <!-- Logo y botón para minimizar -->
    <div class="custom-logo-section">
      <img src="{{ asset('images/mest.jpeg') }}" alt="Logo" class="custom-logo">
      <h1 class="custom-logo-text" id="logo-text">Mest Analytics</h1>
      <button class="custom-toggle-btn" id="toggle-btn">
        <i class="fas fa-bars"></i>
      </button>
    </div>

    <!-- Menú -->
    <div class="custom-menu-section">
      <h3 class="custom-menu-title">Menú</h3>
      <ul>
        <li class="custom-active">
          <a href="{{ route('dashboard') }}" style="text-decoration: none; color: inherit;">
            <i class="fas fa-cube"></i>
            <span>Vista General</span>
          </a>
        </li>
      </ul>
      <h3 class="custom-menu-title">Usuarios</h3>
      <ul>
        <li>
          <i class="fas fa-user"></i>
          <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
            <span>Clientes</span>
          </a>
        </li>
      </ul>
      <h3 class="custom-menu-title">Listados Generales</h3>
      <ul>
        <li>
          <i class="fas fa-bolt"></i>
          <a href="{{ route('clientes.index') }}" style="text-decoration: none; color: inherit;">
            <span>Datos CFE</span>
          </a>
        </li>
        <li>
          <i class="fas fa-chart-bar"></i>
          <a href="{{ route('mediciones.index') }}" style="text-decoration: none; color: inherit;">
            <span>Datos PMZ</span>
          </a>
        </li>
        <li>
          <i class="fas fa-cube"></i>
          <a href="{{ route('areas_carga.index') }}" style="text-decoration: none; color: inherit;">
            <span>Areas de Carga</span>
          </a>
        </li>
        <li>
          <i class="fas fa-cube"></i>
          <a href="{{ route('tarifas.index') }}" style="text-decoration: none; color: inherit;">
            <span>Fixed Inputs</span>
          </a>
        </li>
        <li>
          <i class="fas fa-cube"></i>
          <a href="{{ route('usuarios') }}" style="text-decoration: none; color: inherit;">
            <span>Mediciones</span>
          </a>
        </li>
        <li>
          <i class="fas fa-cube"></i>
          <a href="{{ route('inicio') }}" style="text-decoration: none; color: inherit;">
            <span>Mediciones</span>
          </a>
        </li>
      </ul>
      <h3 class="custom-menu-title">Sistema</h3>
      <ul>
        <li>
          <i class="fas fa-cog"></i>
          <a href="{{ route('config') }}" style="text-decoration: none; color: inherit;">
            <span>Configuración</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Cerrar sesión -->
    <div class="custom-logout">
      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
      </form>
      <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
      </a>
    </div>
  </div>

  <!-- Script para el toggle del sidebar -->
  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggle-btn');
    const logoText = document.getElementById('logo-text');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      logoText.classList.toggle('hidden');
    });
  </script>
</body>
</html>
