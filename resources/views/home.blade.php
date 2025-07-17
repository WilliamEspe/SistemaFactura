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
    .text-sky { color: #0ea5e9; }
    .text-lime { color: #84cc16; }
    .text-rose { color: #f43f5e; }
    .text-fuchsia { color: #d946ef; }

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

    <!-- Métricas -->
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

    <!-- Accesos rápidos -->
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
