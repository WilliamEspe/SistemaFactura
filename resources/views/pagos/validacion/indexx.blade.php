@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Validación de Pagos</h2>
        <div class="flex space-x-4">
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Dashboard
            </a>
            <a href="{{ route('pagos.validacion.historial') }}" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                Ver Historial
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Pagos Pendientes
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $pagosPendientes->count() }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-400 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Monto Total Pendiente
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                ${{ number_format($pagosPendientes->sum('monto'), 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-400 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Pagos Más Antiguos
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                @if($pagosPendientes->isNotEmpty())
                                    {{ $pagosPendientes->first()->created_at->diffForHumans() }}
                                @else
                                    Sin pagos pendientes
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de pagos pendientes -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Pagos Pendientes de Validación</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Lista de pagos que requieren aprobación o rechazo
            </p>
        </div>
        
        @if($pagosPendientes->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay pagos pendientes</h3>
                <p class="mt-1 text-sm text-gray-500">Todos los pagos han sido procesados.</p>
            </div>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach($pagosPendientes as $pago)
                    <li>
                        <div class="px-4 py-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-yellow-800">{{ substr($pago->cliente->name, 0, 2) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">
                                            Pago #{{ $pago->id }}
                                        </div>
                                        <div class="ml-2">
                                            @switch($pago->tipo_pago)
                                                @case('efectivo')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        💵 Efectivo
                                                    </span>
                                                    @break
                                                @case('tarjeta')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        💳 Tarjeta
                                                    </span>
                                                    @break
                                                @case('transferencia')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        🏦 Transferencia
                                                    </span>
                                                    @break
                                                @case('cheque')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        📄 Cheque
                                                    </span>
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <span class="font-medium">Cliente:</span> {{ $pago->factura->cliente->nombre }}
                                        <span class="mx-2">•</span>
                                        <span class="font-medium">Factura:</span> #{{ $pago->factura->id }}
                                        <span class="mx-2">•</span>
                                        <span class="font-medium">Monto:</span> ${{ number_format($pago->monto, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Enviado {{ $pago->created_at->diffForHumans() }}
                                        @if($pago->numero_transaccion)
                                            • Trans: {{ $pago->numero_transaccion }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('pagos.validacion.show', $pago) }}" 
                                   class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    Ver Detalles
                                </a>
                                <button onclick="aprobarPago({{ $pago->id }})" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                    Aprobar
                                </button>
                                <button onclick="rechazarPago({{ $pago->id }})" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<!-- Modal para aprobar pago -->
<div id="aprobarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4 text-green-600">Aprobar Pago</h3>
        <form id="aprobarForm" method="POST">
            @csrf
            <p class="mb-4">¿Está seguro de que desea aprobar este pago?</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Observaciones adicionales (opcional)</label>
                <textarea name="observaciones_validacion" rows="3" 
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                          placeholder="Comentarios sobre la validación..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModal('aprobarModal')" 
                        class="px-4 py-2 text-gray-600 bg-gray-200 rounded hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Confirmar Aprobación
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para rechazar pago -->
<div id="rechazarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4 text-red-600">Rechazar Pago</h3>
        <form id="rechazarForm" method="POST">
            @csrf
            <p class="mb-4">¿Por qué desea rechazar este pago?</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Motivo del rechazo *</label>
                <textarea name="motivo_rechazo" rows="3" required
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                          placeholder="Explique el motivo del rechazo..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModal('rechazarModal')" 
                        class="px-4 py-2 text-gray-600 bg-gray-200 rounded hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Confirmar Rechazo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function aprobarPago(pagoId) {
    document.getElementById('aprobarForm').action = `/pagos/validacion/${pagoId}/aprobar`;
    document.getElementById('aprobarModal').classList.remove('hidden');
    document.getElementById('aprobarModal').classList.add('flex');
}

function rechazarPago(pagoId) {
    document.getElementById('rechazarForm').action = `/pagos/validacion/${pagoId}/rechazar`;
    document.getElementById('rechazarModal').classList.remove('hidden');
    document.getElementById('rechazarModal').classList.add('flex');
}

function cerrarModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}
</script>
@endsection
