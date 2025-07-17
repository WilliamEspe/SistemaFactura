@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-4">Papelera de Usuarios</h2>
        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('usuarios.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Regresar
            </a>
        </div>
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                {{ session('error') }}
            </div>  
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-4">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Motivo de eliminación</th>
                        <th class="px-4 py-2">Eliminado el</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr class="border-b">
                            <td class="px-4 py-2">{{ $usuario->name }}</td>
                            <td class="px-4 py-2">{{ $usuario->email }}</td>
                            <td class="px-4 py-2">{{ $usuario->motivo_eliminacion }}</td>
                            <td class="px-4 py-2">
                                {{ $usuario->deleted_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <!-- Restaurar con motivo -->
                                <button onclick="openModal('restaurarModal-{{ $usuario->id }}')"
                                    class="text-green-600 hover:underline mr-2">Restaurar</button>

                                <!-- Aquí pondremos luego Eliminar definitivamente -->
                                <button onclick="openModal('eliminarDefinitivo-{{ $usuario->id }}')"
                                    class="text-red-600 hover:underline">Eliminar Definitivo</button>

                            </td>
                        </tr>

                        <!-- Modal Restaurar -->
                        <div id="restaurarModal-{{ $usuario->id }}"
                            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                            <div class="bg-white p-6 rounded shadow-md w-96">
                                <h3 class="text-lg font-bold mb-4">Restaurar Usuario</h3>
                                <form method="POST" action="{{ route('usuarios.restaurar', $usuario->id) }}">
                                    @csrf
                                    <label class="block mb-2 text-sm">Motivo de restauración</label>
                                    <textarea name="motivo_restauracion" required class="w-full border rounded px-2 py-1 mb-4"
                                        rows="3" placeholder="Explica por qué se restaura el usuario..."></textarea>

                                    <div class="flex justify-end">
                                        <button type="button" onclick="closeModal('restaurarModal-{{ $usuario->id }}')"
                                            class="mr-2 px-4 py-1">Cancelar</button>
                                        <button type="submit"
                                            class="bg-green-600 text-white px-4 py-1 rounded">Restaurar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal eliminar definitivo -->
                        <div id="eliminarDefinitivo-{{ $usuario->id }}"
                            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                            <div class="bg-white p-6 rounded shadow-md w-96">
                                <h3 class="text-lg font-bold mb-4 text-red-600">¿Eliminar Permanentemente?</h3>
                                <p class="mb-3 text-sm text-gray-700">Esta acción no se puede deshacer. Ingrese su contraseña
                                    para confirmar.</p>

                                <form method="POST" action="{{ route('usuarios.eliminarDefinitivo', $usuario->id) }}">
                                    @csrf
                                    @method('DELETE')

                                    <label class="block mb-1 text-sm">Contraseña actual:</label>
                                    <input type="password" name="password" class="w-full px-2 py-1 border rounded mb-2"
                                        required>

                                    <label class="block text-sm mb-2">
                                        <input type="checkbox" name="confirmacion" class="mr-1"
                                            onchange="document.getElementById('btnDef-{{ $usuario->id }}').disabled = !this.checked">
                                        Estoy consciente de que esta acción es irreversible.
                                    </label>

                                    <div class="flex justify-end mt-4">
                                        <button type="button" onclick="closeModal('eliminarDefinitivo-{{ $usuario->id }}')"
                                            class="mr-2 px-4 py-1">Cancelar</button>
                                        <button type="submit" id="btnDef-{{ $usuario->id }}"
                                            class="bg-red-600 text-white px-4 py-1 rounded" disabled>Eliminar
                                            Definitivamente</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No hay usuarios en papelera.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
@endsection