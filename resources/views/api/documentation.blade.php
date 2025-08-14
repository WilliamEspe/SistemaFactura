<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API REST - Documentación</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-blue-900 mb-2">
                <i class="fas fa-code mr-3"></i>API REST - Sistema de Facturación Segura
            </h1>
            <p class="text-gray-600">Documentación completa para desarrolladores</p>
        </div>

        <!-- Información General -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Información General
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Base URL:</h3>
                    <code class="bg-gray-100 px-3 py-1 rounded text-sm">{{ url('/api') }}</code>
                </div>
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Autenticación:</h3>
                    <code class="bg-gray-100 px-3 py-1 rounded text-sm">Bearer Token</code>
                </div>
            </div>
        </div>

        <!-- Endpoints de Pagos -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-credit-card text-green-600 mr-2"></i>Endpoints de Pagos
            </h2>

            <!-- Registrar Pago -->
            <div class="border border-gray-200 rounded-lg p-4 mb-4">
                <div class="flex items-center mb-3">
                    <span class="bg-green-500 text-white px-3 py-1 rounded text-sm font-semibold mr-3">POST</span>
                    <code class="text-gray-800 font-mono">/api/pagos</code>
                </div>
                
                <p class="text-gray-600 mb-3">Registrar un nuevo pago para una factura</p>
                
                <h4 class="font-medium text-gray-800 mb-2">Headers:</h4>
                <div class="bg-gray-900 text-green-400 p-3 rounded font-mono text-sm mb-3">
Content-Type: application/json<br>
Authorization: Bearer YOUR_TOKEN
                </div>

                <h4 class="font-medium text-gray-800 mb-2">Body (JSON):</h4>
                <div class="bg-gray-900 text-green-400 p-3 rounded font-mono text-sm mb-3">
{<br>
&nbsp;&nbsp;"factura_id": 123,<br>
&nbsp;&nbsp;"monto": 100.50,<br>
&nbsp;&nbsp;"metodo_pago": "transferencia",<br>
&nbsp;&nbsp;"referencia": "REF123456",<br>
&nbsp;&nbsp;"descripcion": "Pago de factura"<br>
}
                </div>

                <h4 class="font-medium text-gray-800 mb-2">Respuesta exitosa (201):</h4>
                <div class="bg-gray-900 text-green-400 p-3 rounded font-mono text-sm">
{<br>
&nbsp;&nbsp;"success": true,<br>
&nbsp;&nbsp;"message": "Pago registrado correctamente",<br>
&nbsp;&nbsp;"pago": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;"id": 456,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"factura_id": 123,<br>
&nbsp;&nbsp;&nbsp;&nbsp;"monto": "100.50",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"estado": "pendiente",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"metodo_pago": "transferencia",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"referencia": "REF123456"<br>
&nbsp;&nbsp;}<br>
}
                </div>
            </div>

            <!-- Consultar Pagos -->
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <span class="bg-blue-500 text-white px-3 py-1 rounded text-sm font-semibold mr-3">GET</span>
                    <code class="text-gray-800 font-mono">/api/pagos</code>
                </div>
                
                <p class="text-gray-600 mb-3">Obtener lista de pagos del cliente autenticado</p>
                
                <h4 class="font-medium text-gray-800 mb-2">Headers:</h4>
                <div class="bg-gray-900 text-green-400 p-3 rounded font-mono text-sm mb-3">
Authorization: Bearer YOUR_TOKEN
                </div>

                <h4 class="font-medium text-gray-800 mb-2">Parámetros opcionales:</h4>
                <ul class="text-sm text-gray-600 mb-3">
                    <li>• <code>estado</code>: pendiente, aprobado, rechazado</li>
                    <li>• <code>factura_id</code>: ID de factura específica</li>
                </ul>
            </div>
        </div>

        <!-- Códigos de Estado -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-list text-purple-600 mr-2"></i>Códigos de Estado HTTP
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-semibold">200</span>
                    <span class="ml-2">OK - Solicitud exitosa</span>
                </div>
                <div>
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-semibold">201</span>
                    <span class="ml-2">Created - Recurso creado</span>
                </div>
                <div>
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">400</span>
                    <span class="ml-2">Bad Request - Error en los datos</span>
                </div>
                <div>
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">401</span>
                    <span class="ml-2">Unauthorized - Token inválido</span>
                </div>
                <div>
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">403</span>
                    <span class="ml-2">Forbidden - Sin permisos</span>
                </div>
                <div>
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">422</span>
                    <span class="ml-2">Validation Error - Datos inválidos</span>
                </div>
            </div>
        </div>

        <!-- Notas Importantes -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-yellow-900 mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>Notas Importantes
            </h2>
            
            <ul class="text-yellow-800 space-y-2">
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-yellow-600 mr-2 mt-1"></i>
                    <span>Todos los pagos registrados vía API requieren validación del administrador</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-yellow-600 mr-2 mt-1"></i>
                    <span>El estado inicial de un pago será siempre "pendiente"</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-yellow-600 mr-2 mt-1"></i>
                    <span>El token de autenticación debe ser proporcionado por el administrador</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-yellow-600 mr-2 mt-1"></i>
                    <span>Los montos deben ser números positivos con máximo 2 decimales</span>
                </li>
            </ul>
        </div>

        <!-- Contacto -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-envelope text-blue-600 mr-2"></i>Contacto
            </h2>
            
            <p class="text-gray-600">
                Para obtener un token de acceso o resolver dudas técnicas, contacta al administrador del sistema.
            </p>
        </div>
    </div>
</body>
</html>
