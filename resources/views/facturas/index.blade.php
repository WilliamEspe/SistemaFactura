@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
        <h2 class="text-2xl font-bold mb-4">Listado de Facturas</h2>
        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                {{ $errors->first('msg') }}
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('facturas.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                Nueva Factura
            </a>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <table class="w-full table-auto text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facturas as $factura)
                        <tr class="border-b {{ $factura->anulada ? 'bg-gray-100 text-gray-500' : '' }}">
                            <td class="px-4 py-2">{{ $factura->id }}</td>
                            <td class="px-4 py-2">{{ $factura->cliente->nombre }}</td>
                            <td class="px-4 py-2">${{ number_format($factura->total, 2) }}</td>
                            <td class="px-4 py-2">
                                @if($factura->anulada)
                                    <span class="text-red-600 font-semibold">Anulada</span>
                                @else
                                    <span class="text-green-600 font-semibold">Válida</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <button onclick="openModal('verFactura-{{ $factura->id }}')"
                                    class="text-blue-600 hover:underline">Ver</button>

                                @if(!$factura->anulada && ($factura->user_id == $usuarioId || $roles->contains('Administrador')))
                                    <button onclick="openModal('anularFactura-{{ $factura->id }}')"
                                        class="text-red-600 hover:underline">Anular</button>
                                @endif
                                <a href="{{ route('facturas.pdf', $factura) }}" class="text-indigo-600 hover:underline">PDF</a>
                                @if($factura->cliente->email_verified_at)
                                    <button onclick="openModal('notificarFactura-{{ $factura->id }}')"
                                        class="text-yellow-600 hover:underline">Notificar</button>
                                @else
                                    <span class="text-gray-400 italic">Correo no verificado</span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Modales --}}
        @foreach($facturas as $factura)
            <!-- Modal Ver Detalle -->
            <div id="verFactura-{{ $factura->id }}"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white p-6 rounded shadow-md w-[600px] max-h-[80vh] overflow-y-auto">
                    <h3 class="text-lg font-bold mb-2">Factura #{{ $factura->id }}</h3>
                    <p><strong>Cliente:</strong> {{ $factura->cliente->nombre }}</p>
                    <p><strong>Total:</strong> ${{ number_format($factura->total, 2) }}</p>
                    <p><strong>Estado:</strong> {{ $factura->anulada ? 'Anulada' : 'Válida' }}</p>
                    @if($factura->anulada)
                        <p class="text-sm text-red-600 font-semibold mt-2">Esta factura fue anulada.</p>
                    @endif
                    <hr class="my-3">
                    <h4 class="font-semibold mb-2">Detalles:</h4>
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-2 py-1">Producto</th>
                                <th class="px-2 py-1">Cantidad</th>
                                <th class="px-2 py-1">P. Unitario</th>
                                <th class="px-2 py-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($factura->detalles as $detalle)
                                <tr>
                                    <td class="px-2 py-1">{{ $detalle->producto->nombre }}</td>
                                    <td class="px-2 py-1">{{ $detalle->cantidad }}</td>
                                    <td class="px-2 py-1">${{ number_format($detalle->precio_unitario, 2) }}</td>
                                    <td class="px-2 py-1">${{ number_format($detalle->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4 text-right">
                        <button onclick="closeModal('verFactura-{{ $factura->id }}')"
                            class="px-4 py-2 bg-gray-500 text-white rounded">Cerrar</button>
                    </div>
                </div>
            </div>

            <!-- Modal Anular -->
            <div id="anularFactura-{{ $factura->id }}"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white p-6 rounded shadow-md w-96">
                    <h3 class="text-lg font-bold mb-4">¿Anular Factura #{{ $factura->id }}?</h3>
                    <p>Esta acción restaurará el stock de los productos.</p>
                    <form method="POST" action="{{ route('facturas.anular', $factura) }}" class="mt-4">
                        @csrf
                        <div class="flex justify-end">
                            <button type="button" onclick="closeModal('anularFactura-{{ $factura->id }}')"
                                class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Anular</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal Notificar -->
            <div id="notificarFactura-{{ $factura->id }}"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white p-6 rounded shadow-md w-96">
                    <h3 class="text-lg font-bold mb-4">¿Enviar factura al cliente?</h3>
                    <p><strong>Factura:</strong> #{{ $factura->id }}</p>
                    <p><strong>Cliente:</strong> {{ $factura->cliente->nombre }}</p>
                    <p><strong>Correo:</strong> {{ $factura->cliente->email }}</p>

                    <form method="POST" action="{{ route('facturas.enviarPDF', $factura) }}" class="mt-4">
                        @csrf
                        <div class="flex justify-end">
                            <button type="button" onclick="closeModal('notificarFactura-{{ $factura->id }}')"
                                class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                            <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- JS para abrir/cerrar modales -->
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
@endsection