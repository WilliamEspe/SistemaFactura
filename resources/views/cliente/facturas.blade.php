@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Mis Facturas</h1>
            <p class="text-gray-600 mt-2">Consulta y paga tus facturas pendientes</p>
        </div>
        <a href="{{ route('dashboard') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-300">
            <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-500 bg-opacity-75">
                    <i class="fas fa-file-invoice text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-blue-600 text-sm font-medium">Total Facturas</p>
                    <p class="text-blue-800 text-2xl font-semibold">{{ $estadisticas['total_facturas'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-500 bg-opacity-75">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-red-600 text-sm font-medium">Pendientes</p>
                    <p class="text-red-800 text-2xl font-semibold">{{ $estadisticas['facturas_pendientes'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-500 bg-opacity-75">
                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-red-600 text-sm font-medium">Por Pagar</p>
                    <p class="text-red-800 text-xl font-semibold">${{ number_format($estadisticas['total_adeudado'], 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-500 bg-opacity-75">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-green-600 text-sm font-medium">Total Pagado</p>
                    <p class="text-green-800 text-xl font-semibold">${{ number_format($estadisticas['total_pagado'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar por número</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Número de factura..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select id="estado" name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pendiente_pago" {{ request('estado') === 'pendiente_pago' ? 'selected' : '' }}>Pendiente de Pago</option>
                    <option value="pagada" {{ request('estado') === 'pagada' ? 'selected' : '' }}>Pagada</option>
                    <option value="vencida" {{ request('estado') === 'vencida' ? 'selected' : '' }}>Vencida</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-300">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
                <a href="{{ route('cliente.facturas') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition duration-300">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Facturas -->
    @if($facturas->count() > 0)
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Facturas ({{ $facturas->total() }})
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Número
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pagado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Saldo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($facturas as $factura)
                            @php
                                $totalPagado = $factura->pagos->where('estado', 'aprobado')->sum('monto');
                                $saldoPendiente = $factura->total - $totalPagado;
                                $pagosRechazados = $factura->pagos->where('estado', 'rechazado');
                                $pagosPendientes = $factura->pagos->where('estado', 'pendiente');
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $factura->numero_factura }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $factura->created_at->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $factura->created_at->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($factura->total, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-green-600">
                                        ${{ number_format($totalPagado, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold {{ $saldoPendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        ${{ number_format($saldoPendiente, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($factura->estado === 'pendiente') bg-yellow-100 text-yellow-800 
                                        @elseif($factura->estado === 'pendiente_pago') bg-orange-100 text-orange-800 
                                        @elseif($factura->estado === 'pagada') bg-green-100 text-green-800 
                                        @elseif($factura->estado === 'vencida') bg-red-100 text-red-800 
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $factura->estado)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex flex-col space-y-2">
                                        @if($pagosPendientes->count() > 0)
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs border border-yellow-300">
                                                <i class="fas fa-clock mr-1"></i>Pago Pendiente Validación
                                            </span>
                                        @elseif($pagosRechazados->count() > 0 && $saldoPendiente > 0)
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs border border-red-300">
                                                <i class="fas fa-times mr-1"></i>Pago Rechazado
                                            </span>
                                            <div class="flex space-x-1">
                                                <a href="{{ route('cliente.pagar-factura', $factura->id) }}" 
                                                   class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs transition duration-300 font-medium"
                                                   title="Pagar vía formulario web">
                                                    <i class="fas fa-credit-card mr-1"></i>Pagar Web
                                                </a>
                                                <button onclick="mostrarInfoAPI({{ $factura->id }}, {{ $saldoPendiente }})" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition duration-300 font-medium"
                                                        title="Ver información de API REST para pagos">
                                                    <i class="fas fa-code mr-1"></i>Pagar API
                                                </button>
                                            </div>
                                        @elseif($saldoPendiente > 0)
                                            <div class="flex space-x-1">
                                                <a href="{{ route('cliente.pagar-factura', $factura->id) }}" 
                                                   class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs transition duration-300 font-medium"
                                                   title="Pagar vía formulario web">
                                                    <i class="fas fa-credit-card mr-1"></i>Pagar Web
                                                </a>
                                                <button onclick="mostrarInfoAPI({{ $factura->id }}, {{ $saldoPendiente }})" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition duration-300 font-medium"
                                                        title="Ver información de API REST para pagos">
                                                    <i class="fas fa-code mr-1"></i>Pagar API
                                                </button>
                                            </div>
                                        @else
                                            <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded text-xs text-center border border-gray-300 font-medium">
                                                <i class="fas fa-check mr-1"></i>Pagada
                                            </span>
                                        @endif
                                        
                                        <button onclick="mostrarDetalles({{ $factura->id }})" 
                                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs transition duration-300 font-medium"
                                                title="Ver Detalles">
                                            <i class="fas fa-eye mr-1"></i>Ver
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $facturas->links() }}
            </div>
        </div>
    @else
        <!-- Estado Vacío -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-file-invoice text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-900 mb-2">No hay facturas</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'estado']))
                    No se encontraron facturas que coincidan con los filtros aplicados.
                @else
                    No tienes facturas registradas en el sistema.
                @endif
            </p>
            @if(request()->hasAny(['search', 'estado']))
                <a href="{{ route('cliente.facturas') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition duration-300">
                    <i class="fas fa-times mr-2"></i>Limpiar Filtros
                </a>
            @endif
        </div>
    @endif

    <!-- Información de Ayuda -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-blue-800 mb-4">
            <i class="fas fa-info-circle mr-2"></i>¿Cómo realizar un pago?
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-700">
            <div>
                <p class="font-medium mb-3">
                    <i class="fas fa-credit-card mr-2"></i>Pago Web (Recomendado)
                </p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Haz clic en el botón "Pagar Web" de la factura</li>
                    <li>Completa el formulario con los datos del pago</li>
                    <li>Adjunta comprobante si tienes disponible</li>
                    <li>El pago será enviado para aprobación</li>
                </ul>
            </div>
            <div>
                <p class="font-medium mb-3">
                    <i class="fas fa-code mr-2"></i>Pago vía API REST
                </p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Haz clic en "Pagar API" para ver instrucciones</li>
                    <li>Obtén un token de acceso del administrador</li>
                    <li>Usa los endpoints documentados</li>
                    <li>Para desarrolladores e integraciones</li>
                </ul>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-blue-200">
            <p class="font-medium mb-2">Contacto para soporte:</p>
            <div class="flex flex-wrap gap-4 text-sm">
                <span><i class="fas fa-envelope mr-1"></i>facturacion@empresa.com</span>
                <span><i class="fas fa-phone mr-1"></i>+1 (555) 123-4567</span>
                <span><i class="fas fa-clock mr-1"></i>Lun-Vie 9:00 AM - 6:00 PM</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles (simple) -->
<div id="detalleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Detalles de Factura</h3>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="contenidoDetalle">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de Información API -->
<div id="apiInfoModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-5 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-screen overflow-y-auto">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-blue-900">
                    <i class="fas fa-code text-blue-600 mr-2"></i>Información de Pago vía API REST
                </h3>
                <button onclick="cerrarModalAPI()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="contenidoAPI">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>
                </button>
            </div>
            <div id="contenidoDetalle">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
function mostrarDetalles(facturaId) {
    // Aquí podrías hacer una llamada AJAX para obtener los detalles
    // Por simplicidad, mostraremos información básica
    const modal = document.getElementById('detalleModal');
    const contenido = document.getElementById('contenidoDetalle');
    
    contenido.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>
            <p class="mt-2">Cargando detalles...</p>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Simular carga (en producción sería una llamada AJAX)
    setTimeout(() => {
        contenido.innerHTML = `
            <p class="text-gray-600">
                Para ver los detalles completos de la factura, utiliza el sistema de administración 
                o contacta con nuestro departamento de facturación.
            </p>
            <div class="mt-4 text-center">
                <button onclick="cerrarModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Cerrar
                </button>
            </div>
        `;
    }, 1000);
}

function mostrarInfoAPI(facturaId, saldoPendiente) {
    const modal = document.getElementById('apiInfoModal');
    const contenido = document.getElementById('contenidoAPI');
    
    contenido.innerHTML = `
        <div class="space-y-6">
            <!-- Información de la Factura -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 mb-2">
                    <i class="fas fa-file-invoice mr-2"></i>Información de Pago
                </h4>
                <p class="text-blue-800">Factura ID: <span class="font-mono font-bold">${facturaId}</span></p>
                <p class="text-blue-800">Saldo Pendiente: <span class="font-mono font-bold">$${saldoPendiente.toFixed(2)}</span></p>
            </div>

            <!-- Instrucciones de API -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 mb-3">
                    <i class="fas fa-code mr-2"></i>Instrucciones para Desarrolladores
                </h4>
                
                <div class="space-y-4">
                    <div>
                        <h5 class="font-medium text-gray-800 mb-2">1. Endpoint de Registro de Pago:</h5>
                        <div class="bg-black text-green-400 p-3 rounded font-mono text-sm overflow-x-auto">
POST /api/pagos<br>
Content-Type: application/json<br>
Authorization: Bearer YOUR_TOKEN
                        </div>
                    </div>

                    <div>
                        <h5 class="font-medium text-gray-800 mb-2">2. Payload JSON:</h5>
                        <div class="bg-black text-green-400 p-3 rounded font-mono text-sm overflow-x-auto">
{<br>
&nbsp;&nbsp;"factura_id": ${facturaId},<br>
&nbsp;&nbsp;"monto": ${saldoPendiente.toFixed(2)},<br>
&nbsp;&nbsp;"metodo_pago": "transferencia",<br>
&nbsp;&nbsp;"referencia": "REF123456",<br>
&nbsp;&nbsp;"descripcion": "Pago de factura ${facturaId}"<br>
}
                        </div>
                    </div>

                    <div>
                        <h5 class="font-medium text-gray-800 mb-2">3. Respuesta Exitosa:</h5>
                        <div class="bg-black text-green-400 p-3 rounded font-mono text-sm overflow-x-auto">
{<br>
&nbsp;&nbsp;"success": true,<br>
&nbsp;&nbsp;"message": "Pago registrado correctamente",<br>
&nbsp;&nbsp;"pago": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;"id": 123,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"estado": "pendiente"<br>
&nbsp;&nbsp;}<br>
}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-900 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Importante
                </h4>
                <ul class="text-yellow-800 text-sm space-y-1">
                    <li>• Los pagos registrados vía API requieren validación del administrador</li>
                    <li>• El estado inicial será "pendiente" hasta la aprobación</li>
                    <li>• Asegúrate de tener un token de autenticación válido</li>
                    <li>• La documentación completa está en /api/documentation</li>
                </ul>
            </div>

            <!-- Botones -->
            <div class="flex justify-between">
                <a href="/api/documentation" target="_blank" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-300">
                    <i class="fas fa-book mr-2"></i>Ver Documentación
                </a>
                <button onclick="cerrarModalAPI()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cerrar
                </button>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('detalleModal').classList.add('hidden');
}

function cerrarModalAPI() {
    document.getElementById('apiInfoModal').classList.add('hidden');
}

// Cerrar modales al hacer clic fuera
document.getElementById('detalleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.getElementById('apiInfoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalAPI();
    }
});
</script>
@endsection
