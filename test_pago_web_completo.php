<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\ClienteController;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Factura;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE PAGO WEB COMPLETO ===\n";

// Datos de prueba
$clienteTest = Cliente::where('nombre', 'Cliente Test')->first();
if (!$clienteTest) {
    echo "❌ Cliente Test no encontrado\n";
    exit(1);
}

$facturaTest = $clienteTest->facturas()->first();
if (!$facturaTest) {
    echo "❌ Factura no encontrada para Cliente Test\n";
    exit(1);
}

$tokenTest = $clienteTest->accessTokens()->first();
if (!$tokenTest || !$tokenTest->plain_text_token) {
    echo "❌ Token no encontrado o inválido\n";
    exit(1);
}

echo "✅ Cliente encontrado: {$clienteTest->nombre}\n";
echo "✅ Factura encontrada: ID {$facturaTest->id} - Total: \${$facturaTest->total}\n";
echo "✅ Token encontrado: {$tokenTest->plain_text_token}\n";
echo "\n";

// Crear request simulado
$requestData = [
    'factura_id' => $facturaTest->id,
    'metodo_pago' => 'tarjeta',  // Cambiado de tipo_pago a metodo_pago
    'monto' => 100.00,
    'numero_transaccion' => 'TEST-' . uniqid(),
    'token' => $tokenTest->plain_text_token,
    'notas' => 'Prueba de pago web completo'  // Cambiado de observaciones a notas
];

echo "Datos del request:\n";
foreach ($requestData as $key => $value) {
    echo "  {$key}: {$value}\n";
}
echo "\n";

// Simular autenticación del usuario
$user = $clienteTest->user;
if (!$user) {
    echo "❌ Usuario asociado no encontrado\n";
    exit(1);
}

// Autenticar el usuario manualmente
auth()->login($user);
echo "✅ Usuario autenticado: {$user->email}\n";

// Crear controlador y hacer la llamada
$controller = new ClienteController();
$request = Request::create('/procesar-pago', 'POST', $requestData);

try {
    echo "🔄 Ejecutando procesarPagoWeb...\n";
    $response = $controller->procesarPagoWeb($request);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "✅ Respuesta: Redirección\n";
        echo "   URL: " . $response->getTargetUrl() . "\n";
        
        // Verificar mensajes de sesión
        $session = $response->getSession();
        if ($session && $session->has('success')) {
            echo "   Mensaje de éxito: " . $session->get('success') . "\n";
        }
        if ($session && $session->has('error')) {
            echo "   Mensaje de error: " . $session->get('error') . "\n";
        }
    } else {
        echo "✅ Respuesta: Vista\n";
    }
    
    // Verificar que se creó el pago
    $pagoCreado = \App\Models\Pago::where('factura_id', $facturaTest->id)
        ->where('numero_transaccion', $requestData['numero_transaccion'])
        ->first();
        
    if ($pagoCreado) {
        echo "✅ Pago creado exitosamente!\n";
        echo "   ID: {$pagoCreado->id}\n";
        echo "   Monto: \${$pagoCreado->monto}\n";
        echo "   Estado: {$pagoCreado->estado}\n";
        echo "   Tipo: {$pagoCreado->tipo_pago}\n";
        echo "   Transacción: {$pagoCreado->numero_transaccion}\n";
    } else {
        echo "❌ El pago no se creó\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error durante procesamiento:\n";
    echo "   {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}:{$e->getLine()}\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
