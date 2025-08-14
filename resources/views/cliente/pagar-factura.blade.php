@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Realizar Pago</h1>
            <p class="text-gray-600 mt-2">Factura: #{{ $factura->id }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('cliente.facturas') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>Volver a Facturas
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <h4 class="font-semibold mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>Error al procesar
            </h4>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Información de la Factura -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-file-invoice mr-2"></i>Información de la Factura
            </h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Número:</span>
                    <span class="font-medium">#{{ $factura->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fecha:</span>
                    <span class="font-medium">{{ $factura->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cliente:</span>
                    <span class="font-medium">{{ $factura->cliente->nombre ?? 'Juan Cliente' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Estado:</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($factura->estado === 'pendiente') bg-yellow-100 text-yellow-800 
                        @elseif($factura->estado === 'pendiente_pago') bg-orange-100 text-orange-800 
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $factura->estado ?? 'pendiente pago')) }}
                    </span>
                </div>
                <hr class="my-4">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total de la Factura:</span>
                    <span class="text-blue-600">${{ number_format($factura->total, 2) }}</span>
                </div>
            </div>

            <!-- Productos de la Factura -->
            <div class="mt-6">
                <h4 class="text-md font-medium text-gray-800 mb-3">Productos/Servicios</h4>
                <div class="space-y-2">
                    @forelse($factura->detalles ?? [] as $detalle)
                        <div class="flex justify-between text-sm">
                            <div>
                                <span class="text-gray-800">{{ $detalle->producto->nombre ?? 'Producto' }}</span>
                                <span class="text-gray-500"> x{{ $detalle->cantidad ?? 1 }}</span>
                            </div>
                            <span class="font-medium">${{ number_format($detalle->subtotal ?? 40, 2) }}</span>
                        </div>
                    @empty
                        <div class="flex justify-between text-sm">
                            <div>
                                <span class="text-gray-800">pastelito</span>
                                <span class="text-gray-500"> x1</span>
                            </div>
                            <span class="font-medium">${{ number_format($factura->total, 2) }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            @if(($factura->pagos ?? collect())->count() > 0)
                <!-- Pagos Existentes -->
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-800 mb-3">Pagos Realizados</h4>
                    <div class="space-y-2">
                        @foreach($factura->pagos as $pago)
                            <div class="flex justify-between text-sm bg-green-50 p-2 rounded">
                                <div>
                                    <span class="text-gray-800">{{ $pago->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="text-gray-500">- {{ ucwords(str_replace('_', ' ', $pago->metodo_pago)) }}</span>
                                </div>
                                <span class="font-medium text-green-600">${{ number_format($pago->monto, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="flex justify-between text-sm font-medium">
                            <span>Saldo Pendiente:</span>
                            <span class="text-red-600">${{ number_format($saldoPendiente ?? $factura->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Formulario de Pago -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-payment mr-2"></i>Procesar Pago via API
            </h3>

            <form action="{{ route('cliente.procesar-pago') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="factura_id" value="{{ $factura->id }}">
                
                <div>
                    <label for="monto" class="block text-sm font-medium text-gray-700 mb-2">
                        Monto a Pagar <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">$ </span>
                        <input type="number" 
                               id="monto" 
                               name="monto" 
                               step="0.01"
                               min="0.01"
                               max="{{ $saldoPendiente ?? $factura->total }}"
                               value="{{ old('monto', $saldoPendiente ?? $factura->total) }}"
                               class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('monto') border-red-500 @enderror" 
                               required>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Máximo disponible: $ {{ number_format($saldoPendiente ?? $factura->total, 2) }}
                    </p>
                    @error('monto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <select id="metodo_pago" 
                            name="metodo_pago" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('metodo_pago') border-red-500 @enderror" 
                            required>
                        <option value="">Seleccionar método...</option>
                        <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>
                            💵 Efectivo
                        </option>
                        <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>
                            💳 Tarjeta (Crédito/Débito)
                        </option>
                        <option value="transferencia" {{ old('metodo_pago') === 'transferencia' ? 'selected' : '' }}>
                            🏦 Transferencia Bancaria
                        </option>
                        <option value="cheque" {{ old('metodo_pago') === 'cheque' ? 'selected' : '' }}>
                            📄 Cheque
                        </option>
                    </select>
                    @error('metodo_pago')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="token" class="block text-sm font-medium text-gray-700 mb-2">
                        Token de API <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="token" 
                           name="token" 
                           value="{{ old('token') }}"
                           placeholder="Ingresa tu token de API..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('token') border-red-500 @enderror font-mono text-sm" 
                           required>
                    <p class="mt-1 text-xs text-gray-500">
                        @if($token && $token->plain_text_token)
                            Token disponible: {{ substr($token->plain_text_token, 0, 8) }}****
                        @else
                            <span class="text-red-500">⚠️ No tienes tokens activos disponibles</span>
                        @endif
                    </p>
                    @error('token')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notas" class="block text-sm font-medium text-gray-700 mb-2">
                        Notas Adicionales (Opcional)
                    </label>
                    <textarea id="notas" 
                              name="notas" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Información adicional sobre el pago...">{{ old('notas') }}</textarea>
                </div>

                <!-- Información del Procesamiento -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 mb-2">
                        <i class="fas fa-shield-alt mr-2"></i>Procesamiento Seguro
                    </h4>
                    <p class="text-xs text-blue-700 mb-3">
                        Tu pago será procesado de forma segura a través de nuestra API REST protegida. 
                        El token será validado automáticamente antes de procesar la transacción.
                    </p>
                    <div class="text-xs text-blue-600 bg-blue-100 p-2 rounded">
                        <strong>Cuenta:</strong> {{ $factura->cliente->email ?? auth()->user()->email }}<br>
                        <strong>API Endpoint:</strong> /api/facturas/{id}/pagar<br>
                        <strong>Método:</strong> POST con autenticación Bearer Token
                    </div>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 shadow-lg transform hover:scale-105">
                        <i class="fas fa-credit-card mr-2"></i>
                        Procesar Pago via API
                    </button>
                    <a href="{{ route('cliente.facturas') }}" 
                       class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 text-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Información de Seguridad -->
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-yellow-800 mb-2">
            <i class="fas fa-info-circle mr-2"></i>Información Importante sobre Pagos
        </h4>
        <ul class="text-xs text-yellow-700 space-y-1">
            <li>• El pago se procesa mediante nuestra API REST con autenticación por tokens</li>
            <li>• Tu token debe estar activo y no revocado para procesar el pago</li>
            <li>• Recibirás confirmación inmediata del estado de la transacción</li>
            <li>• Todos los pagos son auditados y registrados de forma segura</li>
            <li>• En caso de problemas, contacta con nuestro departamento de facturación</li>
        </ul>
    </div>

    <!-- Información de Contacto -->
    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">
            <i class="fas fa-question-circle mr-2"></i>¿Necesitas Ayuda?
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
            <div>
                <p class="font-medium">Departamento de Facturación:</p>
                <p><i class="fas fa-envelope mr-1"></i>facturacion@empresa.com</p>
                <p><i class="fas fa-phone mr-1"></i>+1 (555) 123-4567</p>
            </div>
            <div>
                <p class="font-medium">Soporte Técnico:</p>
                <p><i class="fas fa-envelope mr-1"></i>soporte@empresa.com</p>
                <p><i class="fas fa-phone mr-1"></i>+1 (555) 765-4321</p>
            </div>
        </div>
    </div>
</div>
@endsection
