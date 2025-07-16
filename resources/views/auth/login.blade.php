<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mest Analytics</title>
  <link rel="icon" href="/images/icon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Colores principales */
    .mesta-bg        { background-color: #924C2E; }
    .mesta-btn-bg    { background-color: #FBE9D4; }
    .mesta-btn-hover { background-color: #F8E2CB; }
    /* Textos blancos para contraste */
    .text-light      { color: #FFFFFF; }
    /* Placeholder blanco */
    input::placeholder { color: rgba(255,255,255,0.8); }
  </style>
</head>
<body class="h-screen mesta-bg flex items-center justify-center">

  <div class="w-full max-w-md px-6 space-y-8">

    <!-- Logo y título -->
    <div class="flex flex-col items-center space-y-2">
      <!-- Aquí tu SVG o IMG del icono -->
      <img src="/images/icon_white.png" alt="Logo Mest" class="h-18 w-auto" />
      <h1 class="text-2xl font-semibold text-light">Mest</h1>
      <h2 class="text-2xl font-semibold text-light">Analytics</h2>
    </div>

    <!-- Formulario -->
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
      @csrf

      <!-- Email -->
      <div class="relative">
        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-light">
          <!-- Heroicon: mail -->
          <i class="bi bi-envelope"></i>
        </span>
        <input
          type="email"
          name="email"
          required
          placeholder="Correo electrónico"
          class="w-full h-12 pl-12 pr-4 bg-transparent border border-white rounded-lg text-light focus:outline-none focus:ring-2 focus:ring-white"
        />
      </div>

      <!-- Contraseña -->
      <div class="relative">
        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-light">
          <!-- Heroicon: lock closed -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 11c.55 0 1 .45 1 1v2a1 1 0 01-2 0v-2c0-.55.45-1 1-1zm6-1V7a6 6 0 10-12 0v3H4v12h16V10h-2zm-8-3a4 4 0 118 0v3H10V7z" />
          </svg>
        </span>
        <input
          type="password"
          name="password"
          required
          placeholder="Contraseña"
          class="w-full h-12 pl-12 pr-4 bg-transparent border border-white rounded-lg text-light focus:outline-none focus:ring-2 focus:ring-white"
        />
      </div>

      <!-- Olvidó contraseña -->
      <div class="text-right">
        <a href="{{ route('password.request') }}"
           class="text-xs text-light hover:underline">
          ¿Olvidó su contraseña?
        </a>
      </div>

      <!-- Botón enviar -->
      <button
        type="submit"
        class="w-full h-12 mesta-btn-bg rounded-lg flex items-center justify-center font-semibold text-mesta-bg hover:mesta-btn-hover transition"
      >
        Iniciar sesión
      </button>
    </form>

    <!-- Crear cuenta y legal -->
    <div class="text-center space-y-1">
      <p class="font-semibold text-light">Crear cuenta</p>
      <p class="text-xs text-light">
        Consulta nuestro
        <a href="#" class="underline">Aviso de privacidad</a>
        y nuestros
        <a href="#" class="underline">Términos y condiciones</a>
      </p>
    </div>

  </div>

</body>
</html>
