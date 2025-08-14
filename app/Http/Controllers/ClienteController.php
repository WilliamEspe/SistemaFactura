<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\User;
use App\Notifications\VerificarCorreoCliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::with('facturas');

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telefono', 'LIKE', "%{$search}%");
            });
        }

        $clientes = $query->paginate(10)->appends($request->query());
        
        // Obtener tokens de clientes desde la nueva tabla con paginación
        $tokens_query = \App\Models\ClienteAccessToken::with('cliente')->latest();
        
        if ($request->filled('search_token')) {
            $search_token = $request->search_token;
            $tokens_query->whereHas('cliente', function($q) use ($search_token) {
                $q->where('nombre', 'LIKE', "%{$search_token}%")
                  ->orWhere('email', 'LIKE', "%{$search_token}%");
            })->orWhere('name', 'LIKE', "%{$search_token}%");
        }

        $tokens_clientes = $tokens_query->paginate(5, ['*'], 'tokens_page')->appends($request->query());
        
        return view('clientes.index', compact('clientes', 'tokens_clientes'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email|unique:users,email',
            'telefono' => 'required|string|max:15',
            'direccion' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            
            // 1. Crear el usuario asociado con contraseña predeterminada
            $user = User::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'password' => Hash::make('password123'), // Contraseña predeterminada
                'email_verified_at' => now() // Auto-verificar
            ]);
            
            // 2. Asignar rol de Cliente al usuario
            $user->assignRole('Cliente');
            
            // 3. Crear el cliente asociado al usuario
            $cliente = Cliente::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'user_id' => $user->id
            ]);

            // 4. Generar token automáticamente para el cliente
            $tokenData = $cliente->createToken('Token Automático - ' . $request->nombre);
            $plainTextToken = $tokenData['plainTextToken'];

            // 5. Registrar auditoría
            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Registrar Cliente',
                'descripcion' => "Cliente {$request->nombre} registrado con usuario asociado. Contraseña: password123. Token generado automáticamente.",
                'modulo' => 'Clientes',
            ]);

            // 6. Notificar al cliente (opcional, puede incluir credenciales)
            $cliente->notify(new VerificarCorreoCliente());

            DB::commit();
            
            return back()->with([
                'success' => 'Cliente registrado correctamente. Se ha creado un usuario asociado con contraseña: password123',
                'token' => $plainTextToken
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear cliente: ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'required',
            'email' => 'required|email|unique:clientes,email,' . $cliente->id,
        ]);

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Actualizar Cliente',
            'descripcion' => "Cliente {$cliente->nombre} actualizado.",
            'modulo' => 'Clientes',
        ]);

        $cliente->update($request->only('nombre', 'email', 'telefono', 'direccion'));
        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        // Este método no se usa porque ahora hay eliminación suave con motivo
    }

    public function eliminar(Request $request, Cliente $cliente)
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|max:255',
            'confirmacion' => 'accepted'
        ]);

        $cliente->motivo_eliminacion = $request->input('motivo_eliminacion');
        $cliente->delete();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Eliminar Cliente',
            'descripcion' => "Cliente {$cliente->nombre} movido a papelera. Motivo: {$request->input('motivo_eliminacion')}",
            'modulo' => 'Clientes',
        ]);

        return back()->with('success', 'Cliente movido a papelera.');
    }

    public function restaurar(Request $request, $id)
    {
        $request->validate([
            'motivo_restauracion' => 'required|string|max:255',
        ]);

        $cliente = Cliente::onlyTrashed()->findOrFail($id);
        $cliente->restore();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Restaurar Cliente',
            'descripcion' => "Cliente {$cliente->nombre} restaurado. Motivo: {$request->motivo_restauracion}",
            'modulo' => 'Clientes',
        ]);

        return back()->with('success', 'Cliente restaurado correctamente.');
    }

    public function eliminarDefinitivo(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmacion' => 'accepted'
        ]);

        $usuarioAuth = Auth::user();

        if (!Hash::check($request->password, $usuarioAuth->password)) {
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        $cliente = Cliente::onlyTrashed()->findOrFail($id);
        $nombre = $cliente->nombre;
        $cliente->forceDelete();

        Auditoria::create([
            'user_id' => $usuarioAuth->id,
            'accion' => 'Eliminación definitiva de cliente',
            'descripcion' => "El cliente {$nombre} fue eliminado permanentemente desde la papelera.",
            'modulo' => 'Clientes',
        ]);

        return back()->with('success', 'Cliente eliminado permanentemente.');
    }

    public function verificarCorreo(Cliente $cliente, $hash)
    {
        if (sha1($cliente->email) === $hash) {
            $cliente->email_verified_at = now();
            $cliente->save();

            return redirect('/')->with('success', 'Correo verificado correctamente.');
        }

        return abort(403, 'Hash inválido');
    }

    public function papelera(Request $request)
    {
        $query = Cliente::onlyTrashed();

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telefono', 'LIKE', "%{$search}%");
            });
        }

        $clientes = $query->paginate(10)->appends($request->query());
        return view('clientes.papelera', compact('clientes'));
    }

    /**
     * Crear token de acceso para un cliente
     */
    public function crearTokenCliente(Request $request)
    {
        $request->validate([
            'cliente' => 'required|exists:clientes,id',
            'token_name' => 'required|string|max:255',
        ]);

        /** @var Cliente $cliente */
        $cliente = Cliente::find($request->input('cliente'));
        
        // Usar el sistema de tokens de cliente
        $tokenData = $cliente->createToken($request->input('token_name'));
        
        // Extraer solo el token limpio
        $tokenLimpio = $tokenData['plainTextToken'];
        
        // Registrar auditoría
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Crear Token Cliente',
            'descripcion' => "Token '{$request->input('token_name')}' creado para cliente {$cliente->nombre}",
            'modulo' => 'Clientes',
        ]);
        
        // Regresar con el token limpio para mostrarlo al usuario
        return redirect()->route('clientes.index')
            ->with('success', "Token creado correctamente para {$cliente->nombre}")
            ->with('token', $tokenLimpio);
    }

    /**
     * Mostrar las facturas del cliente actual para pago
     */
    public function misFacturas(Request $request)
    {
        // Buscar el cliente por email del usuario autenticado
        $cliente = Cliente::where('email', Auth::user()->email)->first();
        
        if (!$cliente) {
            return redirect()->route('dashboard')
                ->with('error', 'No se encontró un cliente asociado a tu cuenta.');
        }

        $query = $cliente->facturas()->with('detalles.producto', 'pagos');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('numero_factura', 'LIKE', "%{$search}%");
        }

        $facturas = $query->latest()->paginate(10)->appends($request->query());

        // Estadísticas del cliente
        $estadisticas = [
            'total_facturas' => $cliente->facturas()->count(),
            'facturas_pendientes' => $cliente->facturas()->where('estado', '!=', 'pagada')->count(),
            'total_adeudado' => $cliente->facturas()->where('estado', '!=', 'pagada')->sum('total'),
            'total_pagado' => $cliente->facturas()->where('estado', 'pagada')->sum('total'),
        ];

        return view('cliente.facturas', compact('facturas', 'cliente', 'estadisticas'));
    }

    /**
     * Mostrar formulario para pagar una factura específica
     */
    public function pagarFactura(Request $request, $facturaId)
    {
        $user = Auth::user();
        
        // Buscar el cliente por email (asumiendo que User y Cliente comparten email)
        $cliente = Cliente::where('email', $user->email)->first();
        
        if (!$cliente) {
            return redirect()->route('dashboard')->with('error', 'No se encontró un cliente asociado a tu cuenta.');
        }
        
        // Verificar que la factura pertenezca al cliente
        $factura = $cliente->facturas()->with('detalles.producto', 'pagos')->findOrFail($facturaId);
        
        // Calcular saldo pendiente
        $totalPagado = $factura->pagos()->where('estado', 'aprobado')->sum('monto');
        $saldoPendiente = $factura->total - $totalPagado;
        
        if ($saldoPendiente <= 0) {
            return redirect()->route('cliente.facturas')->with('error', 'Esta factura ya está completamente pagada.');
        }

        // Obtener el token activo del cliente
        $token = $cliente->accessTokens()->whereNull('expires_at')->orWhere('expires_at', '>', now())->first();

        return view('cliente.pagar-factura', compact('factura', 'saldoPendiente', 'token'));
    }

    /**
     * Procesar el pago de una factura vía web
     */
    public function procesarPagoWeb(Request $request)
    {
        // Log inicial
        \Log::info('procesarPagoWeb iniciado', ['data' => $request->all()]);
        
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia,cheque',
            'token' => 'required|string|max:255',
            'notas' => 'nullable|string|max:1000',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        \Log::info('Validación pasada');

        $user = Auth::user();
        \Log::info('Usuario autenticado', ['user_id' => $user->id, 'email' => $user->email]);
        
        // Buscar el cliente por email
        $cliente = Cliente::where('email', $user->email)->first();
        
        if (!$cliente) {
            \Log::error('Cliente no encontrado', ['email' => $user->email]);
            return back()->with('error', 'No se encontró un cliente asociado a tu cuenta.');
        }
        
        \Log::info('Cliente encontrado', ['cliente_id' => $cliente->id, 'nombre' => $cliente->nombre]);
        
        // Verificar que la factura pertenezca al cliente
        $factura = $cliente->facturas()->findOrFail($request->factura_id);
        \Log::info('Factura encontrada', ['factura_id' => $factura->id, 'total' => $factura->total]);
        
        // Calcular saldo pendiente
        $totalPagado = $factura->pagos()->where('estado', 'aprobado')->sum('monto');
        $saldoPendiente = $factura->total - $totalPagado;
        
        \Log::info('Cálculos de pago', ['total_pagado' => $totalPagado, 'saldo_pendiente' => $saldoPendiente]);
        
        if ($saldoPendiente <= 0) {
            \Log::warning('Factura ya pagada');
            return back()->with('error', 'Esta factura ya está completamente pagada.');
        }
        
        if ($request->monto > $saldoPendiente) {
            \Log::warning('Monto excede saldo pendiente', ['monto' => $request->monto, 'saldo' => $saldoPendiente]);
            return back()->with('error', 'El monto no puede ser mayor al saldo pendiente.');
        }

        try {
            \Log::info('Validando token');
            // Validar el token del cliente
            $tokenValido = \App\Models\ClienteAccessToken::where('token', hash('sha256', $request->token))
                ->where('cliente_id', $cliente->id)
                ->where(function($query) {
                    $query->whereNull('expires_at')  // Token sin expiración
                          ->orWhere('expires_at', '>', now());  // O token no expirado
                })
                ->exists();
                
            if (!$tokenValido) {
                \Log::error('Token inválido', ['token_hash' => hash('sha256', $request->token)]);
                return back()->with('error', 'Token de API inválido o expirado.');
            }
            
            \Log::info('Token válido');
            
            // Manejar upload de comprobante
            $rutaComprobante = null;
            if ($request->hasFile('comprobante')) {
                $rutaComprobante = $request->file('comprobante')->store('comprobantes', 'public');
                \Log::info('Comprobante subido', ['ruta' => $rutaComprobante]);
            }

            \Log::info('Creando pago', [
                'factura_id' => $factura->id,
                'pagado_por' => $cliente->user_id ?? Auth::id(),
                'monto' => $request->monto,
                'tipo_pago' => $request->metodo_pago,
            ]);

            // Crear el pago
            $pago = Pago::create([
                'factura_id' => $factura->id,
                'pagado_por' => $cliente->user_id ?? Auth::id(), // Usuario que realiza el pago
                'monto' => $request->monto,
                'tipo_pago' => $request->metodo_pago,
                'numero_transaccion' => $request->numero_transaccion ?? 'WEB-' . uniqid(), // Generar número único si no se proporciona
                'observaciones' => $request->notas, // Usar notas como observaciones
                'estado' => 'pendiente',
            ]);

            \Log::info('Pago creado exitosamente', ['pago_id' => $pago->id]);

            // Registrar en auditoría
            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Crear Pago Web',
                'descripcion' => "Pago de \${$request->monto} registrado para factura #{$factura->id} por {$cliente->nombre}",
                'modulo' => 'Pagos',
            ]);

            \Log::info('Auditoría registrada');

            return redirect()->route('cliente.facturas')
                ->with('success', 'Pago registrado correctamente. Será revisado por el administrador.');
                
        } catch (\Exception $e) {
            \Log::error('Error en procesarPagoWeb', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }
}
