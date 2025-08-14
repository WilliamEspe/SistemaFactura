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
                            <p><strong>Estado:</strong> <span class="badge badge-warning">{{ ucfirst($factura->estado ?? 'Pendiente pago') }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> {{ $factura->cliente->nombre ?? 'Juan Cliente' }}</p>
                            <p><strong>Total de la Factura:</strong> ${{ number_format($factura->total, 2) }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Productos/Servicios</h5>
                            <div class="list-group">
                                @forelse($factura->detalles ?? [] as $detalle)
                                    <div class="list-group-item">
                                        {{ $detalle->producto->nombre ?? 'Producto' }} - ${{ number_format($detalle->precio ?? 40, 2) }}
                                    </div>
                                @empty
                                    <div class="list-group-item">
                                        pastelito x1 - $40.00
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5>Procesar Pago via API</h5>
                            
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form action="{{ route('cliente.procesar-pago') }}" method="POST" enctype="multipart/form-data" class="mt-3">
                                @csrf
                                <input type="hidden" name="factura_id" value="{{ $factura->id }}">
                                
                                <div class="form-group">
                                    <label for="monto">Monto a Pagar <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="monto" 
                                           name="monto" 
                                           step="0.01"
                                           min="0.01"
                                           value="{{ old('monto', $factura->total) }}"
                                           required>
                                    <small class="form-text text-muted">Máximo disponible: ${{ number_format($factura->total, 2) }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="metodo_pago">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                        <option value="">Seleccionar método...</option>
                                        <option value="transferencia">Transferencia Bancaria</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="token_api">Token de API <span class="text-danger">*</span></label>
                                    <textarea class="form-control" 
                                              id="token_api" 
                                              name="token_api" 
                                              rows="3" 
                                              placeholder="Ingrese su token de API..."
                                              required>{{ old('token_api') }}</textarea>
                                    <small class="form-text text-muted">Token disponible: 0x4126nNo ****</small>
                                </div>

                                <div class="form-group">
                                    <label for="notas">Notas Adicionales (Opcional)</label>
                                    <textarea class="form-control" 
                                              id="notas" 
                                              name="notas" 
                                              rows="3" 
                                              placeholder="Información adicional sobre el pago...">{{ old('notas') }}</textarea>
                                </div>

                                <div class="alert alert-info">
                                    <h6>Procesamiento Seguro</h6>
                                    <p class="mb-0">Tu pago será procesado de forma segura a través de nuestra API REST utilizando El token será validado automáticamente antes de procesar la transacción.</p>
                                    <hr class="my-2">
                                    <small>
                                        <strong>API Endpoint:</strong> /api/facturas/pago/pagar<br>
                                        <strong>Método:</strong> POST con autenticación Bearer Token
                                    </small>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-credit-card"></i> Procesar Pago vía API
                                    </button>
                                    <button type="button" class="btn btn-secondary ml-2" onclick="window.history.back()">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Importante sobre Pagos -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <h6>Información Importante sobre Pagos</h6>
                        <ul class="mb-0">
                            <li>El pago se procesa mediante nuestra API REST con autenticación por tokens.</li>
                            <li>Tu token debe estar activo y no revocado para procesar el pago.</li>
                            <li>Recibirás confirmación pendiente del resultado de la transacción.</li>
                            <li>Todos los pagos son validados y registrados de forma segura.</li>
                            <li>En caso de problemas, contacta con nuestro departamento de facturación.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
