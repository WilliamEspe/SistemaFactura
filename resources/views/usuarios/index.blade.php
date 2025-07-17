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

        <!-- Búsqueda -->
        <div class="mb-6">
            <form action="{{ route('usuarios.index') }}" method="GET" class="flex">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500 transition-colors duration-200 hover:text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                </div>
                <input type="search" name="search" id="search" class="block w-full p-2 pl-10 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Buscar usuarios..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="px-4 py-2 ml-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
                Buscar
            </button>
            @if(request('search'))
                <a href="{{ route('usuarios.index') }}" class="px-4 py-2 ml-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                Limpiar
                </a>
            @endif
            </form>
        </div>

        <!-- Botón para abrir modal de crear -->
        <button onclick="openModal('crearModal')" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">
            Nuevo Usuario
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
                        <th class="px-4 py-2 text-left">Roles</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usuarios as $usuario)
                        <tr class="border-b">
                            <td class="px-4 py-2">{{ $usuario->name }}</td>
                            <td class="px-4 py-2">{{ $usuario->email }}</td>
                            <td class="px-4 py-2">
                                <form method="POST" action="{{ route('usuarios.asignarRol', $usuario) }}">
                                    @csrf
                                    <select name="roles[]" multiple class="border rounded px-2 py-1 w-full">
                                        @foreach(\App\Models\Role::all() as $rol)
                                            <option value="{{ $rol->id }}" @if($usuario->roles->contains($rol)) selected @endif>
                                                {{ $rol->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="mt-2 bg-green-500 text-white px-2 py-1 rounded">Actualizar</button>
                                </form>
                            </td>
                            <td class="px-4 py-2">
                                @if($usuario->activo)
                                    <span class="text-green-600 font-semibold">Activo</span>
                                @else
                                    <span class="text-red-600 font-semibold">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">

                                <button onclick="openModal('editarModal-{{ $usuario->id }}')"
                                    class="text-blue-600 hover:underline">Editar</button>


                                <button onclick="openModal('eliminarPapelera-{{ $usuario->id }}')"
                                    class="text-red-600 ml-2">Eliminar</button>


                                @if($usuario->activo)

                                    <button onclick="openModal('inactivarModal-{{ $usuario->id }}')"
                                        class="text-yellow-600 hover:underline ml-2">
                                        Inactivar
                                    </button>
                                @else

                                    <form method="POST" action="{{ route('usuarios.toggleEstado', $usuario) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-600 hover:underline ml-2">
                                            Activar
                                        </button>
                                    </form>
                                @endif
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
    </script>
@endsection