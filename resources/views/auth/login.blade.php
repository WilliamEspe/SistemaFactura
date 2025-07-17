<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 h-screen w-screen flex items-center justify-center">

    <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md border border-gray-200">
        <!-- Logo y encabezado -->
        <div class="text-center mb-6">
            <svg class="w-12 h-12 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                <path d="M16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
            </svg>
            <h2 class="text-2xl font-bold mt-2 text-green-600">Bienvenido de Nuevo</h2>
            <p class="text-sm text-gray-600 mt-1">Por favor, inicia sesión en tu cuenta</p>
        </div>

        <!-- Formulario -->
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input id="email" type="email" name="email"
                       class="w-full px-4 py-2 mt-1 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                       required autofocus>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input id="password" type="password" name="password"
                       class="w-full px-4 py-2 mt-1 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                       required>
            </div>

            <!-- Remember + Forgot -->
            <div class="flex items-center justify-between mb-4 text-sm">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="text-green-500 mr-2 rounded">
                    <span class="text-gray-700">Recordarme</span>
                </label>
                
            </div>

            <!-- Botón -->
            <button type="submit"
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md shadow">
                Iniciar sesión
            </button>
        </form>
    </div>

</body>
</html>
