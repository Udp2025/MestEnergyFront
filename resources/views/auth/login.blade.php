<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="/images/icon.png">
    <title>MestAnalytics</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<style>
    
  .mesta_color_bg{
    background-color: #924C2E;
  }

  .mesta_color_hv{
    background-color: #A77058;
  }

  .mesta_color_txt{
    color: #924C2E;
  }
  
</style>
<body class="h-screen bg-gray-100 flex items-center justify-center">

    <!-- Contenedor Principal -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden w-full max-w-2xl flex">

        <!-- Imagen -->
        <div class="w-1/3 bg-cover bg-center" style="background-image: url('/images/login-image.jpg');">
        </div>

        <!-- Formulario -->
        <div class="w-2/3 p-10 flex flex-col justify-center mb-2 ">
            <h2 class="text-3xl font-semibold text-green-700 mb-2">
                <span class="font-serif mesta_color_txt" style="margin-right: -10px;">MEST</span>
                <span class="font-sans text-gray-800">Analtytics</span>
             </h2>
            <h3  class="font-medium text-gray-500 mb-4">¡Bienvenido! Inicia sesión en tu cuenta.</h3>


            <!-- Formulario -->
            <form method="POST" action="{{ route('login') }}" >
                @csrf
                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-800">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required placeholder="Email"
                        class="mt-2 p-2 w-full border rounded-lg focus:ring focus:ring-white-200">
                </div>

                <!-- Contraseña -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-800">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Password"
                        class="mt-2 p-2 w-full border rounded-lg focus:ring focus:ring-blue-200">
                </div>

                <!-- Recordar Sesión -->
                <div class="flex items-center justify-between mb-8">
                    <label for="remember" class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-800">Recordar sesión</span>
                    </label>
<!--                     <a href="{{ route('password.request') }}" class="text-sm text-gray-500 hover:underline">¿Olvidaste tu contraseña?</a> -->
                </div>

                <!-- Botón -->
                <div class="mb-4">
                    <button type="submit" 
                        class="w-full mesta_color_bg hover:mesta_color_hv text-white font-semibold py-2 px-4 rounded-lg">
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <!-- Enlace al Registro -->
           <p class="text-sm text-center text-gray-600 mt-4">
                ¿No tienes cuenta? 
                <a href="{{ route('register') }}" class="text-gray-500 hover:underline">Regístrate aquí</a>
            </p> 
            <p class="text-sm text-center mt-4">
                 <a href="{{ route('password.request') }}" class="text-gray-500 hover:underline">¿Olvidaste tu contraseña?</a>
            </p>

        </div>
    </div>
    <a href="https://www.mest.energy/" class="fixed top-8 left-8 mesta_color_bg text-white py-2 px-6 rounded-lg shadow-lg hover:mesta_color_hv">
    Visita MestCorp
</a>


</body>
</html>
