@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
    <h2 class="text-2xl font-bold mb-4">Gestión de Clientes</h2>
    <div class="flex justify-between items-center mb-4">
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Dashboard
            </a>
        </div>

    <!-- Botón para abrir el modal de crear -->
    <button onclick="openModal('crearModal')" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">
        Nuevo Cliente
    </button>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Teléfono</th>
                    <th class="px-4 py-2 text-left">Dirección</th>
                    <th class="px-4 py-2">Verificación</th>
                    <th class="px-4 py-2 text-left">Acciones</th>

                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $cliente)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $cliente->nombre }}</td>
                        <td class="px-4 py-2">{{ $cliente->email }}</td>
                        <td class="px-4 py-2">{{ $cliente->telefono }}</td>
                        <td class="px-4 py-2">{{ $cliente->direccion }}</td>
                        <td class="px-4 py-2">
                            @if($cliente->email_verified_at)
                                <span class="text-green-600 font-semibold">Verificado</span>
                            @else
                                <span class="text-red-600 font-semibold">No Verificado</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <button onclick="openModal('editarModal-{{ $cliente->id }}')" class="text-blue-600 hover:underline">Editar</button>
                            <button onclick="openModal('eliminarModal-{{ $cliente->id }}')" class="text-red-600 ml-2">Eliminar</button>
                        </td>
                    </tr>

                    <!-- Modal Editar -->
                    <div id="editarModal-{{ $cliente->id }}" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                        <div class="bg-white p-6 rounded shadow-md w-96">
                            <h3 class="text-lg font-bold mb-4">Editar Cliente</h3>
                            <form method="POST" action="{{ route('clientes.update', $cliente) }}">
                                @csrf @method('PUT')
                                <input type="text" name="nombre" value="{{ $cliente->nombre }}" class="w-full mb-2 px-2 py-1 border rounded" placeholder="Nombre">
                                <input type="email" name="email" value="{{ $cliente->email }}" class="w-full mb-2 px-2 py-1 border rounded" placeholder="Correo">
                                <input type="text" name="telefono" value="{{ $cliente->telefono }}" class="w-full mb-2 px-2 py-1 border rounded" placeholder="Teléfono">
                                <input type="text" name="direccion" value="{{ $cliente->direccion }}" class="w-full mb-2 px-2 py-1 border rounded" placeholder="Dirección">
                                <div class="flex justify-end">
                                    <button type="button" onclick="closeModal('editarModal-{{ $cliente->id }}')" class="mr-2 px-4 py-1">Cancelar</button>
                                    <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Eliminar -->
                    <div id="eliminarModal-{{ $cliente->id }}" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                        <div class="bg-white p-6 rounded shadow-md w-96">
                            <h3 class="text-lg font-bold mb-4">¿Eliminar Cliente?</h3>
                            <p>Esta acción no se puede deshacer.</p>
                            <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" class="mt-4">
                                @csrf @method('DELETE')
                                <div class="flex justify-end">
                                    <button type="button" onclick="closeModal('eliminarModal-{{ $cliente->id }}')" class="mr-2 px-4 py-1">Cancelar</button>
                                    <button type="submit" class="bg-red-600 text-white px-4 py-1 rounded">Eliminar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear -->
<div id="crearModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow-md w-96">
        <h3 class="text-lg font-bold mb-4">Nuevo Cliente</h3>
        <form method="POST" action="{{ route('clientes.store') }}">
            @csrf
            <input type="text" name="nombre" placeholder="Nombre" class="w-full mb-2 px-2 py-1 border rounded">
            <input type="email" name="email" placeholder="Correo" class="w-full mb-2 px-2 py-1 border rounded">
            <input type="text" name="telefono" placeholder="Teléfono" class="w-full mb-2 px-2 py-1 border rounded">
            <input type="text" name="direccion" placeholder="Dirección" class="w-full mb-2 px-2 py-1 border rounded">
            <div class="flex justify-end">
                <button type="button" onclick="closeModal('crearModal')" class="mr-2 px-4 py-1">Cancelar</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Crear</button>
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
</script>
@endsection
