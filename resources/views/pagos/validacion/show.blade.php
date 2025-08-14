@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8 mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Detalle del Pago #{{ $pago->id }}</h2>
        <div class="flex space-x-4">
            <a href="{{ route('pagos.validacion.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver a la Lista
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

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Información del Pago
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Detalles completos del pago enviado por el cliente
                    </p>
                </div>
                <div class="flex items-center">
                    @switch($pago->estado)
                        @case('pendiente')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                ⏳ Pendiente de Validación
                            </span>
                            @break
                        @case('aprobado')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                ✅ Aprobado
                            </span>
                            @break
                        @case('rechazado')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                ❌ Rechazado
                            </span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">ID del Pago</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">#{{ $pago->id }}</dd>
                </div>
                
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Factura Asociada</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <a href="#" class="text-blue-600 hover:text-blue-900 font-medium">
                            Factura #{{ $pago->factura->id }}
                        </a>
                        <span class="text-gray-500 ml-2">
                            (Total: ${{ number_format($pago->factura->total, 2) }})
                        </span>
                    </dd>
                </div>
                
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Cliente</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-800">
                                        {{ substr($pago->factura->cliente->nombre, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $pago->factura->cliente->nombre }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $pago->factura->cliente->email }}
                                </div>
                            </div>
                        </div>
                    </dd>
                </div>
                
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Monto del Pago</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span class="text-2xl font-bold text-green-600">
                            ${{ number_format($pago->monto, 2) }}
                        </span>
                    </dd>
                </div>
                
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Tipo de Pago</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        @switch($pago->tipo_pago)
                            @case('efectivo')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    💵 Pago en Efectivo
                                </span>
                                @break
                            @case('tarjeta')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    💳 Pago con Tarjeta
                                </span>
                                @break
                            @case('transferencia')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    🏦 Transferencia Bancaria
                                </span>
                                @break
                            @case('cheque')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                    📄 Pago con Cheque
                                </span>
                                @break
                        @endswitch
                    </dd>
                </div>
                
                @if($pago->numero_transaccion)
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Número de Transacción</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm">
                            {{ $pago->numero_transaccion }}
                        </code>
                    </dd>
                </div>
                @endif
                
                @if($pago->observaciones)
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Observaciones del Cliente</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <p class="text-blue-700">{{ $pago->observaciones }}</p>
                        </div>
                    </dd>
                </div>
                @endif
                
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Fecha de Envío</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $pago->created_at->format('d/m/Y H:i:s') }}
                        <span class="text-gray-500 ml-2">
                            ({{ $pago->created_at->diffForHumans() }})
                        </span>
                    </dd>
                </div>
                
                @if($pago->validado_por)
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Validado Por</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">
                                        {{ substr($pago->validadoPor->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $pago->validadoPor->name }}
                                </div>
                                @if($pago->fecha_validacion)
                                <div class="text-xs text-gray-500">
                                    {{ $pago->fecha_validacion->format('d/m/Y H:i:s') }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </dd>
                </div>
                @endif
                
                @if($pago->observaciones_validacion || $pago->motivo_rechazo)
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        @if($pago->estado === 'rechazado')
                            Motivo del Rechazo
                        @else
                            Observaciones de Validación
                        @endif
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        @if($pago->estado === 'rechazado' && $pago->motivo_rechazo)
                            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                                <p class="text-red-700">{{ $pago->motivo_rechazo }}</p>
                            </div>
                        @elseif($pago->observaciones_validacion)
                            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                                <p class="text-green-700">{{ $pago->observaciones_validacion }}</p>
                            </div>
                        @endif
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    @if($pago->estado === 'pendiente')
    <!-- Acciones de validación -->
    <div class="mt-6 bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Acciones de Validación
            </h3>
            <div class="flex space-x-4">
                <button onclick="aprobarPago({{ $pago->id }})" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Aprobar Pago
                </button>
                <button onclick="rechazarPago({{ $pago->id }})" 
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Rechazar Pago
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal para aprobar pago -->
<div id="aprobarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4 text-green-600">Aprobar Pago</h3>
        <form id="aprobarForm" method="POST">
            @csrf
            <p class="mb-4">¿Está seguro de que desea aprobar este pago por <strong>${{ number_format($pago->monto, 2) }}</strong>?</p>
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
            <p class="mb-4">¿Por qué desea rechazar este pago por <strong>${{ number_format($pago->monto, 2) }}</strong>?</p>
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
