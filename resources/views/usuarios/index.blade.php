@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">

        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
            </svg>
            <h2 class="text-3xl font-semibold text-gray-800">Gestión de Usuarios</h2>
            </div>
            <a href="{{ route('dashboard') }}" class="flex items-center bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-300 shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Regresar al Dashboard
            </a>
        </div>

        <!-- Búsqueda y filtros -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-6">
            <form action="{{ route('usuarios.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" id="search" class="block w-full p-2.5 pl-10 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Buscar usuarios..." value="{{ request('search') }}">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Buscar
                    </button>
                    @if(request()->hasAny(['search']))
                        <a href="{{ route('usuarios.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Botones de acción -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex gap-2">
                <button onclick="openModal('crearModal')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuevo Usuario
                </button>
            </div>
            
            <div class="text-sm text-gray-600">
                Mostrando {{ $usuarios->firstItem() ?? 0 }} - {{ $usuarios->lastItem() ?? 0 }} de {{ $usuarios->total() }} usuarios
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Tabla de Usuarios -->
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Nombre</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Roles</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Estado</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Tokens API</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($usuarios as $usuario)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $usuario->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $usuario->email }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('usuarios.asignarRol', $usuario) }}">
                                            @csrf
                                            <select name="roles[]" multiple class="border rounded px-2 py-1 w-full text-sm" size="3">
                                                @foreach(\App\Models\Role::where('nombre', '!=', 'Cliente')->get() as $rol)
                                                    <option value="{{ $rol->id }}" @if($usuario->roles->contains($rol)) selected @endif>
                                                        {{ $rol->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Nota: El rol "Cliente" se asigna automáticamente al crear un cliente
                                            </div>
                                            <button type="submit" class="mt-1 bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                                Actualizar
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($usuario->activo)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($usuario->tokens->count() > 0)
                                            <div class="space-y-1">
                                                @foreach($usuario->tokens->take(2) as $token)
                                                    <div class="flex items-center justify-between bg-gray-100 rounded px-2 py-1">
                                                        <div class="flex items-center">
                                                            <span class="text-xs font-medium text-gray-700">{{ $token->name }}</span>
                                                            <span class="ml-2 text-xs text-gray-500">
                                                                {{ $token->created_at->format('d/m/Y') }}
                                                            </span>
                                                        </div>
                                                        <form method="POST" action="{{ route('usuarios.revocarToken', $token->id) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs" onclick="return confirm('¿Revocar este token?')">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                                @if($usuario->tokens->count() > 2)
                                                    <div class="text-xs text-gray-500">
                                                        +{{ $usuario->tokens->count() - 2 }} más
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">Sin tokens</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="openModal('editarModal-{{ $usuario->id }}')" class="text-blue-600 hover:text-blue-800 text-sm">
                                                Editar
                                            </button>
                                            <button onclick="openModal('eliminarPapelera-{{ $usuario->id }}')" class="text-red-600 hover:text-red-800 text-sm">
                                                Eliminar
                                            </button>
                                            @if($usuario->activo)
                                                <button onclick="openModal('inactivarModal-{{ $usuario->id }}')" class="text-yellow-600 hover:text-yellow-800 text-sm">
                                                    Inactivar
                                                </button>
                                            @else
                                                <form method="POST" action="{{ route('usuarios.toggleEstado', $usuario) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm">
                                                        Activar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                        <!-- Modal Editar -->
                        <div id="editarModal-{{ $usuario->id }}"
                            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                            <div class="bg-white p-6 rounded shadow-md w-96">
                                <h3 class="text-lg font-bold mb-4">Editar Usuario</h3>
                                <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $usuario->name }}"
                                        class="w-full mb-2 px-2 py-1 border rounded">
                                    <input type="email" name="email" value="{{ $usuario->email }}"
                                        class="w-full mb-2 px-2 py-1 border rounded">
                                    <div class="flex justify-end">
                                        <button type="button" onclick="closeModal('editarModal-{{ $usuario->id }}')"
                                            class="mr-2 px-4 py-1">Cancelar</button>
                                        <button type="submit"
                                            class="bg-green-600 text-white px-4 py-1 rounded">Actualizar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Eliminar -->
                        <div id="eliminarModal-{{ $usuario->id }}"
                            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                            <div class="bg-white p-6 rounded shadow-md w-96">
                                <h3 class="text-lg font-bold mb-4">¿Eliminar Usuario?</h3>
                                <p>Esta acción no se puede deshacer.</p>
                                <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="mt-4">
                                    @csrf @method('DELETE')
                                    <div class="flex justify-end">
                                        <button type="button" onclick="closeModal('eliminarModal-{{ $usuario->id }}')"
                                            class="mr-2 px-4 py-1">Cancelar</button>
                                        <button type="submit" class="bg-red-600 text-white px-4 py-1 rounded">Eliminar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Información de paginación -->
        <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
            <div class="flex-1 flex justify-between sm:hidden">
                @if($usuarios->previousPageUrl())
                    <a href="{{ $usuarios->appends(request()->query())->previousPageUrl() }}" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Anterior
                    </a>
                @endif
                @if($usuarios->nextPageUrl())
                    <a href="{{ $usuarios->appends(request()->query())->nextPageUrl() }}" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Siguiente
                    </a>
                @endif
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium">{{ $usuarios->firstItem() ?? 0 }}</span> a 
                        <span class="font-medium">{{ $usuarios->lastItem() ?? 0 }}</span> de 
                        <span class="font-medium">{{ $usuarios->total() }}</span> resultados
                    </p>
                </div>
                <div>
                    {{ $usuarios->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Tokens de Acceso -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <!-- Formulario para crear tokens -->
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                </svg>
                Crear Token de Acceso
            </h3>
            
            <form action="{{ route('usuarios.crearToken') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-2">Seleccione un usuario</label>
                        <select name="usuario" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="usuario" required>
                            <option value="">Seleccione un usuario</option>
                            @foreach ($usuarios as $usuarioItem)
                                <option value="{{ $usuarioItem->id }}">{{ $usuarioItem->email }} - {{ $usuarioItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="token_name" class="block text-sm font-medium text-gray-700 mb-2">Nombre del Token de Acceso</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="token_name" name="token_name" placeholder="Ingrese un nombre para el token" required>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold shadow-md transition duration-300 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2 2 2 0 01-2 2m-2-2h.01M7 7a2 2 0 00-2 2m0 0a2 2 0 002 2 2 2 0 002-2m-2-2h.01"></path>
                        </svg>
                        Crear Token
                    </button>
                </div>
            </form>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 mr-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                </svg>
                Tokens de Acceso API
            </h3>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('nuevo_token'))
            <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                <h4 class="font-semibold mb-2 text-green-600">¡Token creado exitosamente!</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <p><strong>Usuario:</strong> {{ session('nuevo_token.usuario') }}</p>
                        <p><strong>Email:</strong> {{ session('nuevo_token.email') }}</p>
                    </div>
                    <div>
                        <p><strong>Nombre del Token:</strong> {{ session('nuevo_token.nombre') }}</p>
                        <p><strong>Fecha:</strong> {{ date('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="mb-3">
                    <p><strong>Token de Acceso:</strong></p>
                    <div class="mt-2 p-3 bg-white rounded border border-green-300">
                        <span class="font-mono text-sm break-all text-green-700 select-all">{{ session('nuevo_token.token') }}</span>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-300 p-3 rounded mt-3">
                    <p class="text-sm text-yellow-800">
                        <strong>⚠️ Importante:</strong> Guarda este token en un lugar seguro. Por razones de seguridad, no podrás verlo nuevamente una vez que cierres esta página.
                    </p>
                </div>
                <div class="mt-3 text-sm text-green-600">
                    <p><strong>💡 Uso del Token:</strong></p>
                    <p class="font-mono text-xs bg-gray-100 p-2 rounded mt-1">
                        Authorization: Bearer {{ session('nuevo_token.token') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Token</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token de Acceso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Creación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Uso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tokens as $token)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $token->id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $token->tokenable->email ?? 'Usuario no encontrado' }}</div>
                                <div class="text-sm text-gray-500">{{ $token->tokenable->name ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $token->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    @if($token->plain_text_token)
                                        <span class="font-mono bg-green-100 px-2 py-1 rounded text-xs break-all">{{ $token->plain_text_token }}</span>
                                    @else
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs break-all">{{ substr($token->token, 0, 10) }}...</span>
                                        <div class="mt-1 text-xs text-red-600">Token anterior (solo hash disponible)</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $token->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'Nunca' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($token->expires_at && $token->expires_at->isPast())
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expirado</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" action="{{ route('usuarios.revocarToken', $token->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" onclick="return confirm('¿Estás seguro de revocar este token?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 012 0v4a1 1 0 11-2 0V7zM12 7a1 1 0 112 0v4a1 1 0 11-2 0V7z" clip-rule="evenodd" />
                                        </svg>
                                        Revocar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                No hay tokens de acceso creados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Crear -->
    <div id="crearModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Nuevo Usuario</h3>
            <form method="POST" action="{{ route('usuarios.store') }}">
                @csrf
                <input type="text" name="name" placeholder="Nombre" class="w-full mb-2 px-2 py-1 border rounded">
                <input type="email" name="email" placeholder="Correo" class="w-full mb-2 px-2 py-1 border rounded">
                <input type="password" name="password" placeholder="Contraseña"
                    class="w-full mb-2 px-2 py-1 border rounded">
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('crearModal')" class="mr-2 px-4 py-1">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Mostrar Token Creado -->
    <div id="tokenResultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h3 class="text-lg font-bold mb-4 text-green-600">¡Token Creado Exitosamente!</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Token:</label>
                <div class="bg-gray-100 p-3 rounded border">
                    <code id="tokenValue" class="text-sm break-all"></code>
                </div>
                <p class="text-xs text-red-600 mt-2">
                    ⚠️ Guarda este token ahora. No podrás verlo nuevamente.
                </p>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="copyToken()" class="mr-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Copiar</button>
                <button type="button" onclick="closeModal('tokenResultModal')" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Cerrar</button>
            </div>
        </div>
    </div>
    <!-- Modal Inactivar Usuario -->
    <div id="inactivarModal-{{ $usuario->id }}"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Inactivar Usuario</h3>
            <form method="POST" action="{{ route('usuarios.inactivar', $usuario) }}">
                @csrf
                <label class="block mb-2 text-sm font-medium text-gray-700">Motivo del bloqueo</label>
                <textarea name="motivo_bloqueo" class="w-full border rounded px-2 py-1 mb-4" rows="3" required
                    placeholder="Escribe el motivo aquí..."></textarea>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('inactivarModal-{{ $usuario->id }}')"
                        class="mr-2 px-4 py-1">Cancelar</button>
                    <button type="submit" class="bg-yellow-600 text-white px-4 py-1 rounded">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Eliminar Papelera -->
    <div id="eliminarPapelera-{{ $usuario->id }}"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Eliminar Usuario</h3>
            <form method="POST" action="{{ route('usuarios.eliminar', $usuario) }}">
                @csrf
                @method('PATCH')

                <label class="block mb-2">Motivo:</label>
                <textarea name="motivo_eliminacion" required class="w-full p-2 border rounded mb-2" rows="3"></textarea>

                <label class="block mb-2">
                    <input type="checkbox" name="confirmacion" required class="mr-2"
                        onchange="document.getElementById('btnEliminar{{ $usuario->id }}').disabled = !this.checked">
                    Confirmo que deseo eliminar este usuario.
                </label>

                <div class="flex justify-end mt-4">
                    <button type="button" onclick="closeModal('eliminarPapelera-{{ $usuario->id }}')"
                        class="px-4 py-2 mr-2">Cancelar</button>
                    <button type="submit" id="btnEliminar{{ $usuario->id }}" class="bg-red-600 text-white px-4 py-2 rounded"
                        disabled>Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts para abrir y cerrar modales -->
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function openTokenModal(usuarioId) {
            // Ya no se usa, removido
        }

        function copyToken() {
            const tokenValue = document.getElementById('tokenValue').textContent;
            navigator.clipboard.writeText(tokenValue).then(function() {
                alert('Token copiado al portapapeles');
            });
        }
    </script>
@endsection