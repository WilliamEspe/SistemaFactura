<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            ¡Bienvenido al Sistema de Facturación!
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Selecciona una opción del menú lateral para comenzar a trabajar.
                        </p>
                    </div>

                    <!-- Cards de resumen -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Secretario'))
                            <div class="bg-blue-50 dark:bg-blue-900 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-blue-500 bg-opacity-75">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5">
                                        <p class="text-blue-600 dark:text-blue-300 text-sm font-medium">Clientes</p>
                                        <p class="text-blue-800 dark:text-blue-100 text-lg font-semibold">Gestionar</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('clientes.index') }}" class="text-blue-600 dark:text-blue-300 text-sm hover:underline">Ver clientes →</a>
                                </div>
                            </div>
                        @endif

                        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Bodega'))
                            <div class="bg-green-50 dark:bg-green-900 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-green-500 bg-opacity-75">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 21l-7-4V7l7 4"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5">
                                        <p class="text-green-600 dark:text-green-300 text-sm font-medium">Productos</p>
                                        <p class="text-green-800 dark:text-green-100 text-lg font-semibold">Inventario</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('productos.index') }}" class="text-green-600 dark:text-green-300 text-sm hover:underline">Ver productos →</a>
                                </div>
                            </div>
                        @endif

                        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Ventas'))
                            <div class="bg-yellow-50 dark:bg-yellow-900 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-yellow-500 bg-opacity-75">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5">
                                        <p class="text-yellow-600 dark:text-yellow-300 text-sm font-medium">Facturas</p>
                                        <p class="text-yellow-800 dark:text-yellow-100 text-lg font-semibold">Ventas</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('facturas.index') }}" class="text-yellow-600 dark:text-yellow-300 text-sm hover:underline">Ver facturas →</a>
                                </div>
                            </div>
                        @endif

                        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Pagos'))
                            <div class="bg-purple-50 dark:bg-purple-900 p-6 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-purple-500 bg-opacity-75">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5">
                                        <p class="text-purple-600 dark:text-purple-300 text-sm font-medium">Pagos</p>
                                        <p class="text-purple-800 dark:text-purple-100 text-lg font-semibold">Validación</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('pagos.validacion.index') }}" class="text-purple-600 dark:text-purple-300 text-sm hover:underline">Ver pagos →</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Portal de Cliente --}}
                    @if(Auth::user()->roles->contains('nombre', 'Cliente'))
                        <div class="mt-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Portal de Cliente</h4>
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xl font-bold mb-2">Gestiona tus Facturas y Pagos</h3>
                                        <p class="text-blue-100 mb-4">
                                            Accede a tu portal personalizado para ver tus facturas, realizar pagos mediante API y consultar tu historial.
                                        </p>
                                        <div class="flex space-x-2 text-sm text-blue-100">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Ver facturas
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Pagos por API
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Historial completo
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-6 py-3 bg-white bg-opacity-20 rounded-lg font-semibold">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            API REST Disponible
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Información adicional para clientes --}}
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                    <h5 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ¿Cómo funciona?
                                    </h5>
                                    <p class="text-blue-700 dark:text-blue-300 text-sm">
                                        Para acceder al portal necesitas tu email y token de API. Contacta con administración si no tienes tu token.
                                    </p>
                                </div>
                                
                                <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                                    <h5 class="font-semibold text-green-800 dark:text-green-200 mb-2">
                                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Seguridad
                                    </h5>
                                    <p class="text-green-700 dark:text-green-300 text-sm">
                                        Todos los pagos se procesan de forma segura a través de nuestra API protegida con autenticación por tokens.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(Auth::user()->roles->contains('nombre', 'Administrador'))
                        <div class="mt-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Accesos Administrativos</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <a href="{{ route('usuarios.index') }}" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">Gestión de Usuarios</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Usuarios, roles y tokens</p>
                                    </div>
                                </a>
                                
                                <a href="{{ route('auditoria.index') }}" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">Auditoría del Sistema</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Logs y actividad</p>
                                    </div>
                                </a>

                                <a href="{{ route('usuarios.papelera') }}" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">Papeleras</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Elementos eliminados</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>