@extends('layouts.app')

@section('content')
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .metric-card {
            background-color: #ffffff;
            padding: 1.25rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .metric-card h3 {
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .metric-card p {
            font-size: 2rem;
            font-weight: 700;
        }

        .access-card {
            padding: 1.5rem;
            border-radius: 0.75rem;
            color: white;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            display: block;
            text-decoration: none;
        }

        .access-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .access-card h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }

        .access-card p {
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .chart-container {
            background-color: white;
            padding: 2rem;
            margin-top: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Soporte para modo oscuro */
        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .metric-card,
        .dark .chart-container {
            background-color: #1e293b;
            color: #e2e8f0;
        }

        .dark .access-card {
            opacity: 0.92;
        }

        .dark .access-card:hover {
            opacity: 1;
        }

        /* Colores suaves para texto */
        .text-sky {
            color: #0ea5e9;
        }

        .text-lime {
            color: #84cc16;
        }

        .text-rose {
            color: #f43f5e;
        }

        .text-fuchsia {
            color: #d946ef;
        }

        /* Colores pastel UX para tarjetas */
        .bg-cornflower {
            background-color: #60a5fa;
        }

        .bg-cornflower:hover {
            background-color: #3b82f6;
        }

        .bg-plum {
            background-color: #a78bfa;
        }

        .bg-plum:hover {
            background-color: #8b5cf6;
        }

        .bg-rose {
            background-color: #fb7185;
        }

        .bg-rose:hover {
            background-color: #f43f5e;
        }

        .bg-mint {
            background-color: #34d399;
        }

        .bg-mint:hover {
            background-color: #10b981;
        }

        .bg-warmgray {
            background-color: #9ca3af;
        }

        .bg-warmgray:hover {
            background-color: #6b7280;
        }

        .bg-sunset {
            background-color: #fbbf24;
        }

        .bg-sunset:hover {
            background-color: #f59e0b;
        }

        .bg-indigo-light {
            background-color: #818cf8;
        }

        .bg-indigo-light:hover {
            background-color: #6366f1;
        }

        .bg-fuchsia-500 {
            background-color: #d946ef;
        }

        .bg-fuchsia-500:hover {
            background-color: #c026d3;
        }

        .bg-yellow-400 {
            background-color: #facc15;
        }

        .bg-yellow-400:hover {
            background-color: #eab308;
        }
    </style>


    <div class="max-w-7xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6">Bienvenido, {{ $usuario->name }}</h2>
        
        {{-- Métricas para Clientes --}}
        @if($roles->contains('Cliente') && !$roles->contains('Administrador'))
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="metric-card">
                    <h3>Mis Facturas</h3>
                    <p class="text-sky">{{ App\Models\Factura::whereHas('cliente', function($q) { $q->where('email', Auth::user()->email); })->count() }}</p>
                </div>
                <div class="metric-card">
                    <h3>Total Adeudado</h3>
                    <p class="text-rose">${{ number_format(App\Models\Factura::whereHas('cliente', function($q) { $q->where('email', Auth::user()->email); })->where('estado', '!=', 'pagada')->sum('total'), 2) }}</p>
                </div>
                <div class="metric-card">
                    <h3>Facturas Pagadas</h3>
                    <p class="text-lime">{{ App\Models\Factura::whereHas('cliente', function($q) { $q->where('email', Auth::user()->email); })->where('estado', 'pagada')->count() }}</p>
                </div>
                <div class="metric-card">
                    <h3>Pagos Realizados</h3>
                    <p class="text-fuchsia">{{ App\Models\Pago::whereHas('factura.cliente', function($q) { $q->where('email', Auth::user()->email); })->count() }}</p>
                </div>
            </div>
        @else
            {{-- Métricas para Administradores y otros roles --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="metric-card">
                    <h3>Facturas emitidas</h3>
                    <p class="text-sky">{{ $totalFacturas }}</p>
                </div>
                <div class="metric-card">
                    <h3>Total vendido</h3>
                    <p class="text-lime">${{ number_format($totalVentas, 2) }}</p>
                </div>
                <div class="metric-card">
                    <h3>Productos con bajo stock</h3>
                    <p class="text-rose">{{ $productosBajoStock }}</p>
                </div>
                <div class="metric-card">
                    <h3>Usuarios registrados</h3>
                    <p class="text-fuchsia">{{ $totalUsuarios }}</p>
                </div>
            </div>
        @endif

        <!-- Accesos rápidos -->
        @if($roles->contains('Cliente') && !$roles->contains('Administrador'))
            {{-- Vista específica para Clientes --}}
            <div class="space-y-6">
                {{-- Acceso directo a las facturas del cliente --}}
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Mis Facturas por Pagar</h3>
                            <p class="text-blue-100 mb-4">
                                Consulta y paga tus facturas pendientes de forma segura mediante nuestra API REST.
                            </p>
                            <div class="flex flex-wrap gap-4 text-sm text-blue-100 mb-4">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Ver mis facturas
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Realizar pagos por API
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Historial de pagos
                                </span>
                            </div>
                        </div>
                        <div class="text-right ml-6">
                            <a href="{{ route('cliente.facturas') }}" 
                               class="inline-flex items-center px-8 py-4 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 text-lg">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Ver Mis Facturas
                            </a>
                        </div>
                    </div>
                </div>
                
                {{-- Información adicional para clientes --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ¿Cómo pagar?
                        </h4>
                        <p class="text-gray-600 text-sm mb-4">
                            Para realizar pagos necesitas tu <strong>token de API</strong> generado por el administrador. 
                            Contacta con administración si no lo tienes.
                        </p>
                        <div class="bg-blue-50 p-3 rounded-lg text-xs text-blue-700">
                            <strong>Email:</strong> {{ Auth::user()->email }}<br>
                            <strong>Token:</strong> Lo proporciona administración
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Seguridad de Pagos
                        </h4>
                        <p class="text-gray-600 text-sm mb-4">
                            Los pagos se procesan de forma <strong>segura</strong> a través de nuestra 
                            API REST protegida con autenticación por tokens.
                        </p>
                        <div class="bg-green-50 p-3 rounded-lg text-xs text-green-700">
                            ✓ Conexión SSL encriptada<br>
                            ✓ Autenticación por tokens<br>
                            ✓ Registro completo de auditoría
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Vista para Administradores y otros roles --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @if($roles->contains('Administrador'))
                <a href="{{ route('usuarios.index') }}" class="access-card bg-cornflower hover:bg-blue-700">
                    <h3>Gestión de Usuarios</h3>
                    <p>Ver, editar y asignar roles.</p>
                </a>
            @endif

            @if($roles->contains('Administrador'))
                <a href="{{ route('clientes.index') }}" class="access-card bg-indigo-light hover:bg-indigo-700">
                    <h3>Gestión de Clientes</h3>
                    <p>Administrar datos de clientes.</p>
                </a>
            @endif

            @if($roles->contains('Administrador'))
                <a href="{{ route('productos.index') }}" class="access-card bg-rose hover:bg-rose">
                    <h3>Gestión de Productos</h3>
                    <p>Administrar datos de productos.</p>
                </a>
            @endif

            @if($roles->contains('Administrador'))
                <a href="{{ route('facturas.index') }}" class="access-card bg-mint hover:bg-mint">
                    <h3>Gestión de Facturas</h3>
                    <p>Administrar datos de facturas.</p>
                </a>
            @endif

            @if($roles->contains('Administrador') || $roles->contains('Auditor'))
                <a href="{{ route('auditoria.index') }}" class="access-card bg-warmgray hover:bg-warmgray">
                    <h3>Auditoría</h3>
                    <p>Ver historial de auditoría.</p>
                </a>
            @endif

            @if($roles->contains('Administrador'))
                <a href="{{ route('usuarios.papelera') }}" class="access-card bg-sunset hover:bg-sunset">
                    <h3>Papelera de Usuarios</h3>
                    <p>Ver usuarios eliminados.</p>
                </a>
            @endif

            @if($roles->contains('Secretario'))
                <a href="{{ route('clientes.index') }}" class="access-card bg-mint hover:bg-mint">
                    <h3>Clientes</h3>
                    <p>Gestionar datos de clientes.</p>
                </a>
            @endif

            @if($roles->contains('Secretario') || $roles->contains('Administrador'))
                <a href="{{ route('clientes.papelera') }}" class="access-card bg-cornflower hover:bg-cornflower">
                    <h3>Papelera de Clientes</h3>
                    <p>Ver clientes eliminados.</p>
                </a>
            @endif

            @if ($roles->contains('Bodega') || $roles->contains('Administrador'))
                <a href="{{ route('productos.papelera') }}" class="access-card bg-sunset hover:bg-sunset">
                    <h3>Papelera de Productos</h3>
                    <p>Ver productos eliminados.</p>
                </a>
            @endif

            @if($roles->contains('Bodega'))
                <a href="{{ route('productos.index') }}" class="access-card bg-yellow-400 hover:bg-yellow-500">
                    <h3>Productos</h3>
                    <p>Administrar inventario y stock.</p>
                </a>
            @endif

            @if($roles->contains('Ventas'))
                <a href="{{ route('facturas.index') }}" class="access-card bg-fuchsia-500 hover:bg-fuchsia-600">
                    <h3>Facturación</h3>
                    <p>Emitir y gestionar facturas.</p>
                </a>
            @endif

            {{-- Portal de Cliente --}}
            @if($roles->contains('Cliente'))
                <div class="col-span-full">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white mt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold mb-2">Portal de Cliente - Gestiona tus Facturas y Pagos</h3>
                                <p class="text-blue-100 mb-4">
                                    Accede a tu portal personalizado para ver tus facturas, realizar pagos mediante API REST y consultar tu historial.
                                </p>
                                <div class="flex space-x-4 text-sm text-blue-100 mb-4">
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
                                        Pagos por API REST
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Historial completo
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                        <h5 class="font-semibold mb-1 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            ¿Cómo funciona?
                                        </h5>
                                        <p class="text-sm text-blue-100">
                                            Para acceder necesitas tu email y token de API. Contacta con administración si no tienes tu token.
                                        </p>
                                    </div>
                                    
                                    <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                        <h5 class="font-semibold mb-1 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Seguridad
                                        </h5>
                                        <p class="text-sm text-blue-100">
                                            Todos los pagos se procesan de forma segura a través de nuestra API protegida con autenticación por tokens.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right ml-6">
                                <span class="inline-flex items-center px-8 py-4 bg-white bg-opacity-20 rounded-lg font-semibold text-lg">
                                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    API REST Disponible
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

            @if($roles->contains('Administrador') || $roles->contains('Ventas'))
                <div class="chart-container">
                    <h3 class="text-lg font-semibold mb-4">Ventas Mensuales (últimos 6 meses)</h3>
                    <canvas id="graficoVentas" height="100"></canvas>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    const ctx = document.getElementById('graficoVentas').getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($ventasMensuales->pluck('mes')) !!},
                            datasets: [{
                                label: 'Ventas en USD',
                                data: {!! json_encode($ventasMensuales->pluck('total')) !!},
                                backgroundColor: 'rgba(99, 102, 241, 0.7)'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            @endif
        </div>
    </div>
@endsection