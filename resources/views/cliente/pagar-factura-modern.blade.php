@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Realizar Pago</h4>
                    <a href="{{ route('cliente.facturas') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Facturas
                    </a>
                </div>

                <div class="card-body">
                    <!-- Información de la Factura -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Información de la Factura</h5>
                        </div>
                        
                        <div class="col-md-6">
                            <p><strong>Número:</strong> #{{ $factura->id }}</p>
                            <p><strong>Fecha:</strong> {{ $factura->created_at->format('d/m/Y') }}</p>
                            <p><strong>Estado:</strong> <span class="badge badge-warning">{{ $factura->estado }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> {{ $factura->cliente->nombre }}</p>
                            <p><strong>Total de la Factura:</strong> ${{ number_format($factura->total, 2) }}</p>
                        </div>
                    </div>

                    <hr>

    <!-- Formulario de Pago -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
            <i class="fas fa-credit-card text-green-600 mr-2"></i>Registrar Pago
        </h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('cliente.procesar-pago') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="factura_id" value="{{ $factura->id }}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto -->
                <div>
                    <label for="monto" class="block text-sm font-medium text-gray-700 mb-2">
                        Monto a Pagar <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="monto" 
                           name="monto" 
                           step="0.01"
                           min="0.01"
                           max="{{ $saldoPendiente }}"
                           value="{{ old('monto', $saldoPendiente) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Máximo: ${{ number_format($saldoPendiente, 2) }}</p>
                </div>

                <!-- Método de Pago -->
                <div>
                    <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <select id="metodo_pago" 
                            name="metodo_pago" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Seleccionar método</option>
                        <option value="transferencia" {{ old('metodo_pago') === 'transferencia' ? 'selected' : '' }}>Transferencia Bancaria</option>
                        <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>Tarjeta de Crédito/Débito</option>
                    </select>
                </div>

                <!-- Referencia -->
                <div>
                    <label for="referencia" class="block text-sm font-medium text-gray-700 mb-2">
                        Referencia <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="referencia" 
                           name="referencia" 
                           value="{{ old('referencia') }}"
                           placeholder="Número de transacción, recibo, etc."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <!-- Comprobante -->
                <div>
                    <label for="comprobante" class="block text-sm font-medium text-gray-700 mb-2">
                        Comprobante de Pago
                    </label>
                    <input type="file" 
                           id="comprobante" 
                           name="comprobante" 
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (máx. 5MB)</p>
                </div>
            </div>

            <!-- Descripción -->
            <div class="mt-6">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                    Descripción (Opcional)
                </label>
                <textarea id="descripcion" 
                          name="descripcion" 
                          rows="3"
                          placeholder="Detalles adicionales sobre el pago..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('descripcion') }}</textarea>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 mt-6">
                <a href="{{ route('cliente.facturas') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar Pago
                </button>
            </div>
        </form>
    </div>

    <!-- Información Adicional -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Información Importante
        </h3>
        <ul class="text-blue-800 space-y-2">
            <li class="flex items-start">
                <i class="fas fa-arrow-right text-blue-600 mr-2 mt-1"></i>
                <span>Tu pago será registrado con estado "pendiente" y requiere aprobación del administrador</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-arrow-right text-blue-600 mr-2 mt-1"></i>
                <span>Te notificaremos cuando tu pago sea aprobado o rechazado</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-arrow-right text-blue-600 mr-2 mt-1"></i>
                <span>Si adjuntas un comprobante, facilitará la verificación del pago</span>
            </li>
        </ul>
    </div>
</div>
@endsection
