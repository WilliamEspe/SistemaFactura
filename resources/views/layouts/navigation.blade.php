<!-- Sidebar con estilos mejorados y comportamiento responsive -->
<aside class="fixed top-0 left-0 h-full w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 shadow-md z-50 overflow-y-auto" id="sidebar">
    <style>
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-link:hover {
            background-color: #f1f5f9;
            color: #2563eb;
        }

        .sidebar-link svg {
            margin-right: 0.5rem;
        }

        .sidebar-section {
            font-size: 0.75rem;
            font-weight: bold;
            color: #6b7280;
            padding: 0.5rem 1rem;
            text-transform: uppercase;
        }

        .sidebar-divider {
            border-top: 1px solid #d1d5db;
            margin: 1rem 0;
        }
    </style>

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 border-b border-gray-300 px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
            </svg>
            <span class="ml-3 text-xl font-bold text-gray-800">FactuSys</span>
        </a>
    </div>

    <!-- Menú de opciones -->
    <nav class="mt-4">
        <a href="{{ route('dashboard') }}" class="sidebar-link">🏠 <span class="ml-2">Inicio</span></a>

        @if(Auth::user()->roles->contains('nombre', 'Administrador'))
            <a href="{{ route('usuarios.index') }}" class="sidebar-link">👥 <span class="ml-2">Usuarios</span></a>
        @endif

        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Secretario'))
            <a href="{{ route('clientes.index') }}" class="sidebar-link">🧑‍💼 <span class="ml-2">Clientes</span></a>
        @endif

        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Bodega'))
            <a href="{{ route('productos.index') }}" class="sidebar-link">📦 <span class="ml-2">Productos</span></a>
        @endif

        @if(Auth::user()->roles->contains('nombre', 'Administrador') || Auth::user()->roles->contains('nombre', 'Ventas'))
            <a href="{{ route('facturas.index') }}" class="sidebar-link">🧾 <span class="ml-2">Facturas</span></a>
        @endif

        @if(Auth::user()->roles->contains('nombre', 'Administrador'))
            <div class="sidebar-divider"></div>
            <div class="sidebar-section">Administración</div>

            <a href="{{ route('auditoria.index') }}" class="sidebar-link">📊 <span class="ml-2">Auditoría</span></a>
            <a href="{{ route('usuarios.papelera') }}" class="sidebar-link">🗑️ <span class="ml-2">Papelera de usuarios</span></a>
            <a href="{{ route('clientes.papelera') }}" class="sidebar-link">🗑️ <span class="ml-2">Papelera de clientes</span></a>
            <a href="{{ route('productos.papelera') }}" class="sidebar-link">🗑️ <span class="ml-2">Papelera de productos</span></a>
        @endif

        <div class="sidebar-divider"></div>
        <a href="{{ route('profile.edit') }}" class="sidebar-link">🙍‍♂️ <span class="ml-2">Perfil</span></a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left sidebar-link">🚪 <span class="ml-2">Cerrar sesión</span></button>
        </form>
    </nav>
</aside>

<script>
    // Scroll automático al inicio en móviles
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('sidebar').scrollTop = 0;
    });
</script>
