<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title')</title>
  <!-- Estilos -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <!-- Fuentes -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Iconos -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    /* Estilos Globales */
    body {
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 0;
      background: #ecf0f1;
      color: #333;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* Navbar Superior */
    .custom-navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #ffffff;
      padding: 10px 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .navbar-left {
      display: flex;
      align-items: center;
    }

    .navbar-logo {
      display: flex;
      align-items: center;
    }

    .navbar-logo img {
      height: 50px;
      width: auto;
      transition: transform 0.3s ease;
      /* Aseguramos que el logo y el texto estén centrados verticalmente */
      vertical-align: middle;
    }

    .navbar-logo img:hover {
      transform: scale(1.05);
    }

    .navbar-logo span {
      margin-left: 10px;
      font-size: 24px;
      font-weight: 700;
      color: #F68D2E;
      vertical-align: middle;
    }

    .navbar-center {
      flex: 1;
      text-align: center;
      font-size: 16px;
      font-weight: 500;
      color: #555;
    }

    .navbar-right {
      display: flex;
      align-items: center;
      position: relative;
    }

    /* Se restaura el estilo original del avatar */
    .custom-avatar {
      position: relative;
      cursor: pointer;
      margin-right: 10px;
    }

    .custom-avatar img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 2px solid #fff;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s ease;
    }

    .custom-avatar img:hover {
      transform: scale(1.1);
    }

    .custom-profile-menu {
      display: none;
      position: absolute;
      top: 30px;
      right: 0;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      width: 150px;
      padding: 20px;
      z-index: 10;
    }

    .custom-profile-header {
      text-align: center;
      border-bottom: 1px solid #f1f1f1;
      padding-bottom: 15px;
      margin-bottom: 15px;
    }

    .custom-profile-header img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #F68D2E;
      box-shadow: 0 0 8px rgba(246, 141, 46);
    }

    .custom-profile-header h3 {
      margin: 0;
      font-size: 18px;
      color: #333;
    }

    .custom-profile-header p {
      margin: 5px 0 0;
      font-size: 14px;
      color: #555;
    }

    .custom-profile-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .custom-profile-actions a {
      text-decoration: none;
      padding: 10px;
      text-align: center;
      background: #ffffff;
      color: #000000;
      border-radius: 5px;
      font-size: 14px;
      transition: background 0.3s ease;
      display: block;
    }

    .custom-profile-actions a:hover {
      background: #F68D2E;
      color: #fff;
    }

    .custom-profile-actions .custom-logout-btn {
      text-decoration: none;
      padding: 10px;
      text-align: center;
      background: #ffffff;
      color: #000000;
      border-radius: 5px;
      font-size: 14px;
      transition: background 0.3s ease;
      display: block;
    }

    .custom-profile-actions .custom-logout-btn:hover {
      background: #F68D2E;
      color: #fff;
    }

    /* Notificaciones */
    .custom-notification-icon {
      margin-right: 20px;
      cursor: pointer;
      font-size: 20px;
      position: relative;
    }

    .custom-notifications {
      display: none;
      position: absolute;
      top: 35px;
      right: 0;
      background: #fff;
      border: 1px solid #ccc;
      padding: 10px;
      width: 220px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      z-index: 10;
      border-radius: 4px;
    }

    /* Menú Horizontal Debajo del Navbar */
    .custom-horizontal-menu {
      background: #ffffff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      padding: 0 20px;
    }

    .custom-horizontal-menu ul {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: flex-start;
    }

    .custom-horizontal-menu ul li {
      position: relative;
    }

    .custom-horizontal-menu ul li a {
      display: block;
      padding: 12px 20px;
      text-decoration: none;
      color: #333;
      font-size: 15px;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    .custom-horizontal-menu ul li a:hover {
      background: #f0f0f0;
    }

    .custom-horizontal-menu ul li .submenu {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background: #fff;
      min-width: 160px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      z-index: 100;
      border-radius: 4px;
      overflow: hidden;
    }

    .custom-horizontal-menu ul li:hover .submenu {
      display: block;
    }

    .custom-horizontal-menu ul li .submenu li a {
      padding: 10px 15px;
      font-size: 14px;
    }

    .custom-horizontal-menu ul li .submenu li a:hover {
      background: #f7f7f7;
    }

    /* Contenido Principal */
    .custom-content {
      padding: 20px;
    }
  </style>
</head>

<body>
  @php
    use Illuminate\Support\Facades\Storage;
    $profileImageUrl = asset('images/mest.jpeg');
    if (Auth::check() && Auth::user()->profile_image) {
      $disk = config('filesystems.images_disk', 'public');
      $path = Auth::user()->profile_image;
      if ($disk === 's3') {
        $profileImageUrl = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(10));
      } else {
        $profileImageUrl = Storage::disk($disk)->url($path);
      }
    }
  @endphp

  <!-- Navbar Superior -->
  <header class="custom-navbar">
    <div class="navbar-left">
      <div class="navbar-logo">
        <a href="{{ route('home') }}">
          <img src="{{ asset('images/mest.jpeg') }}" alt="Logo">
          <span>Mest Analytics</span>
        </a>
      </div>
    </div>
    <!--     <div class="navbar-center">
       <span>Bienvenido a la Plataforma de Análisis</span>
    </div> -->
    <div class="custom-navbar-right">
      <div class="custom-notification-icon" id="notification-icon">
        <i class="fas fa-bell"></i>
        <div class="custom-notifications" id="notifications">
          <p>Sin notificaciones</p>
        </div>
      </div>

      <div class="custom-avatar" id="avatar">
      <img src="{{ $profileImageUrl }}" alt="Large User Avatar">        <div class="custom-profile-menu" id="profile-menu">
          <div class="custom-profile-header">
            <img src="{{ $profileImageUrl }}" alt="Large User Avatar">
            <h3>{{ Auth::user()->name }}</h3> <!-- Nombre del usuario -->
            <p>{{ Auth::user()->email }}</p> <!-- Correo del usuario -->
          </div>

          <div class="custom-profile-actions">
            <a href="{{ route('profile.edit') }}">
              <i class="fas fa-user"></i>
              Editar perfil
            </a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="custom-logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      </div>

      <span id="time"></span>
    </div>
  </header>

  <!-- Menú Horizontal Debajo del Navbar -->
  <nav class="custom-horizontal-menu">
    <ul>
      <li class="menu-item">
        <a href="{{ route('inicio') }}">Dasboard</a>
      </li>
      <li class="menu-item">
        <a href="#">Energy</a>
        <ul class="submenu">
          <li><a href="{{ route('usuarios') }}">Time View</a></li>
          <li><a href="{{ route('heatmap') }}">Heat Map</a></li>
          <li><a href="{{ route('benchmarking') }}">Benchmarking</a></li>
          <li><a href="{{ route('energyflow') }}">Energy Flow</a></li>
 
        </ul>
      </li>
      <li class="menu-item">
        <a href="#">Operational</a>
        <ul class="submenu">
          <li><a href="{{ route('site_alerts') }}">Site Alerts</a></li>
          <li><a href="{{ route('tiggers') }}">Tiggers</a></li>
          <li><a href="{{ route('manage') }}">Manage Events</a></li>
        </ul>
      </li>
      <li class="menu-item">
        <a href="{{ route('groups') }}">Groups</a>
      </li>
    </ul>
  </nav>

  <!-- Contenido Principal -->
  <main class="custom-content">
    @yield('content')
  </main>

  <!-- Scripts para el Navbar -->
  <script>
    // Actualiza la hora cada segundo
    function updateTime() {
      const now = new Date();
      document.getElementById('time').textContent = now.toLocaleTimeString();
    }
    setInterval(updateTime, 1000);

    // Toggle para el menú de perfil
    document.getElementById('avatar').addEventListener('click', function(event) {
      event.stopPropagation();
      var profileMenu = document.getElementById('profile-menu');
      profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
    });

    // Toggle para las notificaciones
    document.getElementById('notification-icon').addEventListener('click', function(event) {
      event.stopPropagation();
      var notifications = document.getElementById('notifications');
      notifications.style.display = (notifications.style.display === 'block') ? 'none' : 'block';
    });

    // Cierra los menús si se hace clic fuera de ellos
    window.addEventListener('click', function(event) {
      if (!event.target.closest('#avatar')) {
        document.getElementById('profile-menu').style.display = 'none';
      }
      if (!event.target.closest('#notification-icon')) {
        document.getElementById('notifications').style.display = 'none';
      }
    });
  </script>
</body>

</html>
