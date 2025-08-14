<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\FacturaDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

// Incluir helper de auditoría
require_once app_path('Helpers/auditoria.php');

/**
 * @group Facturas API
 * 
 * API endpoints para gestionar facturas
 */
class FacturaApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validar parámetros de entrada
            $request->validate([
                'search' => 'nullable|string|max:255',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
                'estado' => 'nullable|in:pendiente,pagada,anulada',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort' => 'nullable|in:numero_factura,total,created_at',
                'order' => 'nullable|in:asc,desc'
            ]);

            $query = Factura::with(['cliente:id,nombre,email', 'detalles.producto:id,nombre']);

            // Control de acceso basado en roles
            if ($user->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                // Admin/Ventas/Secretario pueden ver todas las facturas
            } elseif ($user->roles->where('nombre', 'Cliente')->count()) {
                // Clientes solo pueden ver sus propias facturas
                $query->where('cliente_id', $user->id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver facturas',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Aplicar filtros
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('numero_factura', 'ILIKE', "%{$search}%")
                      ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                          $clienteQuery->where('nombre', 'ILIKE', "%{$search}%")
                                       ->orWhere('email', 'ILIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->input('cliente_id'));
            }

            if ($request->filled('estado')) {
                switch ($request->input('estado')) {
                    case 'pendiente':
                        $query->where('estado', 'pendiente')->where('anulada', false);
                        break;
                    case 'pagada':
                        $query->where('estado', 'pagada')->where('anulada', false);
                        break;
                    case 'anulada':
                        $query->where('anulada', true);
                        break;
                }
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
            }

            // Ordenamiento
            $sort = $request->input('sort', 'created_at');
            $order = $request->input('order', 'desc');
            $query->orderBy($sort, $order);

            // Paginación
            $perPage = min($request->input('per_page', 15), 100);
            $facturas = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $facturas,
                'message' => 'Facturas obtenidas exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al obtener facturas: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Verificar permisos
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear facturas',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Validar datos de entrada
            $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'productos' => 'required|array|min:1|max:50',
                'productos.*.producto_id' => 'required|integer|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1|max:9999',
                'productos.*.precio_unitario' => 'nullable|numeric|min:0|max:999999.99'
            ]);

            DB::beginTransaction();

            // Verificar cliente
            $cliente = Cliente::find($request->cliente_id);
            if (!$cliente->activo) {
                throw new \Exception('El cliente está inactivo');
            }

            // Crear factura
            $numeroFactura = $this->generarNumeroFactura();
            
            $factura = Factura::create([
                'numero_factura' => $numeroFactura,
                'cliente_id' => $request->cliente_id,
                'user_id' => $request->user()->id,
                'subtotal' => 0,
                'impuesto' => 0,
                'total' => 0,
                'estado' => 'pendiente',
                'anulada' => false
            ]);

            $subtotal = 0;

            // Procesar productos
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);
                
                // Verificar stock disponible
                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                $precioUnitario = $item['precio_unitario'] ?? $producto->precio;
                $totalLinea = $precioUnitario * $item['cantidad'];
                
                // Crear detalle de factura
                FacturaDetalle::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $totalLinea
                ]);

                // Actualizar stock
                $producto->decrement('stock', $item['cantidad']);
                $subtotal += $totalLinea;
            }

            // Calcular impuestos (12%)
            $impuesto = $subtotal * 0.12;
            $total = $subtotal + $impuesto;

            // Actualizar totales de la factura
            $factura->update([
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total
            ]);

            DB::commit();

            // Cargar relaciones para la respuesta
            $factura->load(['cliente:id,nombre,email', 'detalles.producto:id,nombre,precio', 'user:id,name']);

            // Registrar auditoría
            registrarAuditoria('create', 'Factura creada: ' . $numeroFactura . ' para cliente: ' . $cliente->nombre, 'facturas');

            return response()->json([
                'success' => true,
                'data' => $factura,
                'message' => 'Factura creada exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear factura: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de factura inválido',
                    'error_code' => 'INVALID_INVOICE_ID'
                ], 400);
            }

            $factura = Factura::with([
                'cliente:id,nombre,email,telefono,direccion',
                'detalles.producto:id,nombre,descripcion',
                'user:id,name,email',
                'pagos:id,monto,estado,created_at'
            ])->find($id);

            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada',
                    'error_code' => 'INVOICE_NOT_FOUND'
                ], 404);
            }

            // Verificar permisos
            $user = $request->user();
            if (!$user->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                if ($user->roles->where('nombre', 'Cliente')->count() && $user->id !== $factura->cliente_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver esta factura',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                } elseif (!$user->roles->where('nombre', 'Cliente')->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver facturas',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $factura,
                'message' => 'Factura obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener factura: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'factura_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de factura inválido',
                    'error_code' => 'INVALID_INVOICE_ID'
                ], 400);
            }

            // Verificar permisos
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Ventas'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para actualizar facturas',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $factura = Factura::find($id);

            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada',
                    'error_code' => 'INVOICE_NOT_FOUND'
                ], 404);
            }

            // No permitir actualizar facturas anuladas o pagadas
            if ($factura->anulada) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede actualizar una factura anulada',
                    'error_code' => 'INVOICE_CANCELLED'
                ], 409);
            }

            if ($factura->estado === 'pagada') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede actualizar una factura pagada',
                    'error_code' => 'INVOICE_PAID'
                ], 409);
            }

            // Validar datos de entrada
            $request->validate([
                'productos' => 'required|array|min:1|max:50',
                'productos.*.producto_id' => 'required|integer|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1|max:9999',
                'productos.*.precio_unitario' => 'nullable|numeric|min:0|max:999999.99'
            ]);

            DB::beginTransaction();

            // Restaurar stock de productos anteriores
            foreach ($factura->detalles as $detalle) {
                $detalle->producto->increment('stock', $detalle->cantidad);
            }

            // Eliminar detalles anteriores
            $factura->detalles()->delete();

            $subtotal = 0;

            // Procesar nuevos productos
            foreach ($request->productos as $item) {
                $producto = Producto::find($item['producto_id']);
                
                // Verificar stock disponible
                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                $precioUnitario = $item['precio_unitario'] ?? $producto->precio;
                $totalLinea = $precioUnitario * $item['cantidad'];
                
                // Crear nuevo detalle
                FacturaDetalle::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $totalLinea
                ]);

                // Actualizar stock
                $producto->decrement('stock', $item['cantidad']);
                $subtotal += $totalLinea;
            }

            // Calcular nuevos totales
            $impuesto = $subtotal * 0.12;
            $total = $subtotal + $impuesto;

            // Actualizar factura
            $factura->update([
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'updated_by' => $request->user()->id
            ]);

            DB::commit();

            // Cargar relaciones actualizadas
            $factura->load(['cliente:id,nombre,email', 'detalles.producto:id,nombre,precio', 'user:id,name']);

            // Registrar auditoría
            registrarAuditoria('update', 'Factura actualizada: ' . $factura->numero_factura, 'facturas');

            return response()->json([
                'success' => true,
                'data' => $factura,
                'message' => 'Factura actualizada exitosamente'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar factura: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'factura_id' => $id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (anular factura).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de factura inválido',
                    'error_code' => 'INVALID_INVOICE_ID'
                ], 400);
            }

            // Solo administradores pueden anular
            if (!$request->user()->roles->where('nombre', 'Administrador')->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para anular facturas',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $factura = Factura::find($id);

            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada',
                    'error_code' => 'INVOICE_NOT_FOUND'
                ], 404);
            }

            if ($factura->anulada) {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura ya está anulada',
                    'error_code' => 'INVOICE_ALREADY_CANCELLED'
                ], 409);
            }

            DB::beginTransaction();

            // Restaurar stock de productos
            foreach ($factura->detalles as $detalle) {
                $detalle->producto->increment('stock', $detalle->cantidad);
            }

            // Marcar como anulada
            $factura->update([
                'anulada' => true,
                'estado' => 'anulada',
                'anulada_por' => $request->user()->id,
                'anulada_at' => now()
            ]);

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('cancel', 'Factura anulada: ' . $factura->numero_factura, 'facturas');

            return response()->json([
                'success' => true,
                'message' => 'Factura anulada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular factura: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'factura_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Generar número de factura único
     */
    private function generarNumeroFactura(): string
    {
        $prefix = 'FAC-';
        $year = date('Y');
        $month = date('m');
        
        // Buscar el último número de factura del mes
        $ultimaFactura = Factura::where('numero_factura', 'LIKE', $prefix . $year . $month . '%')
            ->orderBy('numero_factura', 'desc')
            ->first();

        if ($ultimaFactura) {
            $ultimoNumero = intval(substr($ultimaFactura->numero_factura, -6));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return $prefix . $year . $month . str_pad((string)$nuevoNumero, 6, '0', STR_PAD_LEFT);
    }
}
