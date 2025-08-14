@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-3xl font-semibold text-gray-800">Gestión de Clientes</h2>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-300 shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Regresar al Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('token'))
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded">
            <div class="flex items-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-medium">Token creado exitosamente:</p>
                    <code class="bg-gray-100 p-2 rounded text-sm block mt-2 break-all">{{ session('token') }}</code>
                    <p class="text-xs mt-1">⚠️ Guarda este token. No se mostrará nuevamente.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Barra de búsqueda y acciones -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row gap-4 flex-grow">
                <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-col sm:flex-row gap-4 flex-grow">
                    <div class="flex-grow">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-3 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Buscar por nombre, email, teléfono..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                            Buscar
                        </button>
                        <a href="{{ route('clientes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
            <div class="flex gap-2">
                <button onclick="openModal('crearModal')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors shadow-md flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nuevo Cliente
                </button>
                
                @if(Auth::user()->roles->contains('nombre', 'Administrador'))
                    <button onclick="openModal('tokenModal')" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-colors shadow-md flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                        </svg>
                        Generar Token
                    </button>
                @endif
                
                <a href="{{ route('clientes.papelera') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors shadow-md flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Papelera
                </a>
            </div>
        </div>
    </div>

    <!-- Tabla Principal de Clientes -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Lista de Clientes</h3>
                <span class="text-sm text-gray-600">{{ $clientes->total() }} clientes registrados</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Nombre</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Email</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Teléfono</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-900">Dirección</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-900">Verificación</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-900">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($clientes as $cliente)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cliente->nombre }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $cliente->email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $cliente->telefono }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $cliente->direccion }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($cliente->email_verified_at)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            ✓ Verificado
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            ⏳ Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="openModal('editarModal-{{ $cliente->id }}')" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Editar
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button onclick="openModal('eliminarModal-{{ $cliente->id }}')" 
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-gray-500 text-lg">No se encontraron clientes</p>
                                        @if(request('search'))
                                            <p class="text-gray-400 text-sm">No hay resultados para "{{ request('search') }}"</p>
                                            <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mt-2">Ver todos los clientes</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Información de paginación -->
            <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                <div class="flex-1 flex justify-between sm:hidden">
                    @if($clientes->previousPageUrl())
                        <a href="{{ $clientes->appends(request()->query())->previousPageUrl() }}" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Anterior
                        </a>
                    @endif
                    @if($clientes->nextPageUrl())
                        <a href="{{ $clientes->appends(request()->query())->nextPageUrl() }}" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Siguiente
                        </a>
                    @endif
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium">{{ $clientes->firstItem() ?? 0 }}</span> a 
                            <span class="font-medium">{{ $clientes->lastItem() ?? 0 }}</span> de 
                            <span class="font-medium">{{ $clientes->total() }}</span> resultados
                        </p>
                    </div>
                    <div>
                        {{ $clientes->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Tokens para Clientes (Solo Administradores) -->
    @if(Auth::user()->roles->contains('nombre', 'Administrador'))
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">📋 Tokens de Acceso de Clientes</h3>
            
            @if(session('nuevo_token_cliente'))
                <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                    <h4 class="font-semibold mb-2">¡Token de cliente creado exitosamente!</h4>
                    <p><strong>Cliente:</strong> {{ session('nuevo_token_cliente.cliente') }}</p>
                    <p><strong>Nombre:</strong> {{ session('nuevo_token_cliente.nombre') }}</p>
                    <p><strong>Token de Acceso:</strong></p>
                    <div class="mt-2">
                        <span class="font-mono bg-green-100 px-3 py-2 rounded text-sm break-all border border-green-300">{{ session('nuevo_token_cliente.token') }}</span>
                    </div>
                    <p class="text-sm text-green-600 mt-2">✅ Este token permite acceder a las facturas del cliente vía API.</p>
                </div>
            @endif
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Creación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tokens_clientes ?? [] as $token)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $token->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $token->cliente->nombre ?? 'Cliente no encontrado' }}</div>
                                    <div class="text-sm text-gray-500">{{ $token->cliente->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $token->name }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-mono bg-gray-100 p-2 rounded border max-w-xs overflow-hidden">
                                        <span class="block truncate">{{ substr($token->token ?? 'Token oculto', 0, 20) }}...</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $token->created_at ? $token->created_at->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay tokens de acceso creados para clientes
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Documentación de la API -->
            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                <h4 class="text-md font-semibold mb-3">📚 Documentación de la API para Clientes</h4>
                <div class="space-y-2 text-sm">
                    <p><strong>Endpoint:</strong> <code class="bg-white px-2 py-1 rounded">GET /api/clientes/{cliente_id}/facturas</code></p>
                    <p><strong>Header:</strong> <code class="bg-white px-2 py-1 rounded">Authorization: Bearer {token}</code></p>
                    <p><strong>Respuesta de ejemplo:</strong></p>
                    <pre class="bg-white p-3 rounded text-xs overflow-x-auto"><code>{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero": "FACT-001",
      "fecha": "2024-01-15",
      "total": 150.50,
      "estado": "pagada"
    }
  ],
  "message": "Facturas obtenidas exitosamente"
}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modales para cada cliente -->
@foreach($clientes as $cliente)
    <!-- Modal Editar -->
    <div id="editarModal-{{ $cliente->id }}" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Editar Cliente</h3>
            <form method="POST" action="{{ route('clientes.update', $cliente) }}">
                @csrf
                @method('PUT')
                <input type="text" name="nombre" value="{{ $cliente->nombre }}" placeholder="Nombre" class="w-full mb-2 px-2 py-1 border rounded">
                <input type="email" name="email" value="{{ $cliente->email }}" placeholder="Email" class="w-full mb-2 px-2 py-1 border rounded">
                <input type="text" name="telefono" value="{{ $cliente->telefono }}" placeholder="Teléfono" class="w-full mb-2 px-2 py-1 border rounded">
                <input type="text" name="direccion" value="{{ $cliente->direccion }}" placeholder="Dirección" class="w-full mb-2 px-2 py-1 border rounded">
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('editarModal-{{ $cliente->id }}')" class="mr-2 px-4 py-1">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div id="eliminarModal-{{ $cliente->id }}" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded shadow-md w-120 max-w-lg">
            <h3 class="text-lg font-bold mb-4">Eliminar Cliente</h3>
            <p class="mb-4">¿Estás seguro de que quieres eliminar a {{ $cliente->nombre }}?</p>
            <form method="POST" action="{{ route('clientes.eliminar', $cliente) }}">
                @csrf
                @method('PATCH')
                
                <!-- Campo para motivo de eliminación -->
                <div class="mb-4">
                    <label for="motivo_eliminacion_{{ $cliente->id }}" class="block text-sm font-medium text-gray-700 mb-2">Motivo de eliminación *</label>
                    <textarea 
                        id="motivo_eliminacion_{{ $cliente->id }}" 
                        name="motivo_eliminacion" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        rows="3" 
                        required
                        placeholder="Ingrese el motivo de la eliminación"></textarea>
                </div>
                
                <!-- Checkbox de confirmación -->
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="confirmacion" class="mr-2" required>
                        <span class="text-sm text-gray-700">Confirmo que deseo eliminar este cliente</span>
                    </label>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('eliminarModal-{{ $cliente->id }}')" class="mr-2 px-4 py-1">Cancelar</button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-1 rounded">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<!-- Modal Crear Cliente -->
<div id="crearModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow-md w-96">
        <h3 class="text-lg font-bold mb-4">Nuevo Cliente</h3>
        <form method="POST" action="{{ route('clientes.store') }}">
            @csrf
            <input type="text" name="nombre" placeholder="Nombre" class="w-full mb-2 px-2 py-1 border rounded" required>
            <input type="email" name="email" placeholder="Email" class="w-full mb-2 px-2 py-1 border rounded" required>
            <input type="text" name="telefono" placeholder="Teléfono" class="w-full mb-2 px-2 py-1 border rounded">
            <input type="text" name="direccion" placeholder="Dirección" class="w-full mb-2 px-2 py-1 border rounded">
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('crearModal')" class="mr-2 px-4 py-1">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Crear Token para Cliente (Solo para Administradores) -->
@if(Auth::user()->roles->contains('nombre', 'Administrador'))
<div id="tokenModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow-md w-96">
        <h3 class="text-lg font-bold mb-4">Generar Token para Cliente</h3>
        <form method="POST" action="{{ route('clientes.crearToken') }}">
            @csrf
            <div class="mb-3">
                <label for="cliente" class="block text-sm font-medium text-gray-700 mb-2">Cliente:</label>
                <select name="cliente" class="w-full px-3 py-2 border border-gray-300 rounded-md" id="cliente" required>
                    <option value="">Seleccione un cliente</option>
                    @foreach ($clientes as $clienteItem)
                        <option value="{{ $clienteItem->id }}">{{ $clienteItem->nombre }} ({{ $clienteItem->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="token_name" class="block text-sm font-medium text-gray-700 mb-2">Nombre del Token:</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" 
                       id="token_name" name="token_name" placeholder="Ej: Token API Facturas" required>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="closeModal('tokenModal')" class="mr-2 px-4 py-2 text-gray-600">Cancelar</button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">Crear Token</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Scripts para abrir y cerrar modales -->
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
</script>
@endsection
