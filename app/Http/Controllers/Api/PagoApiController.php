<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

// Incluir helper de auditoría
require_once app_path('Helpers/auditoria.php');

/**
 * @group Pagos API
 * 
 * API endpoints para gestionar pagos
 */
class PagoApiController extends Controller
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
                'estado' => 'nullable|in:pendiente,aprobado,rechazado',
                'tipo_pago' => 'nullable|string|max:50',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort' => 'nullable|in:monto,created_at,validated_at',
                'order' => 'nullable|in:asc,desc'
            ]);

            $query = Pago::with(['factura.cliente:id,nombre,email']);

            // Control de acceso basado en roles
            if ($user->roles->whereIn('nombre', ['Administrador', 'Pagos'])->count()) {
                // Admin/Pagos pueden ver todos los pagos
            } elseif ($user->roles->where('nombre', 'Cliente')->count()) {
                // Clientes solo pueden ver sus propios pagos
                $query->whereHas('factura', function ($q) use ($user) {
                    $q->where('cliente_id', $user->id);
                });
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver pagos',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Aplicar filtros
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('referencia', 'ILIKE', "%{$search}%")
                      ->orWhere('observaciones', 'ILIKE', "%{$search}%")
                      ->orWhereHas('factura.cliente', function ($clienteQuery) use ($search) {
                          $clienteQuery->where('nombre', 'ILIKE', "%{$search}%")
                                       ->orWhere('email', 'ILIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            if ($request->filled('tipo_pago')) {
                $query->where('tipo_pago', 'ILIKE', "%{$request->input('tipo_pago')}%");
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
            $pagos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pagos,
                'message' => 'Pagos obtenidos exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al obtener pagos: ' . $e->getMessage(), [
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
            $user = $request->user();

            // Solo clientes y admin pueden crear pagos
            if (!$user->roles->whereIn('nombre', ['Cliente', 'Administrador'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear pagos',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Validar datos de entrada
            $request->validate([
                'factura_id' => 'required|integer|exists:facturas,id',
                'monto' => 'required|numeric|min:0.01|max:999999.99',
                'tipo_pago' => 'required|string|max:50',
                'referencia' => 'nullable|string|max:100',
                'observaciones' => 'nullable|string|max:500',
                'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120' // 5MB max
            ]);

            DB::beginTransaction();

            // Verificar factura
            $factura = Factura::find($request->factura_id);
            
            // Si es cliente, solo puede pagar sus propias facturas
            if ($user->roles->where('nombre', 'Cliente')->count() && $user->id !== $factura->cliente_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes pagar facturas de otros clientes',
                    'error_code' => 'UNAUTHORIZED_PAYMENT'
                ], 403);
            }

            if ($factura->anulada) {
                throw new \Exception('No se puede pagar una factura anulada');
            }

            if ($factura->estado === 'pagada') {
                throw new \Exception('Esta factura ya está pagada');
            }

            // Verificar que el monto no exceda el saldo pendiente
            $totalPagado = $factura->pagos()->where('estado', 'aprobado')->sum('monto');
            $saldoPendiente = $factura->total - $totalPagado;

            if ($request->monto > $saldoPendiente) {
                throw new \Exception("El monto excede el saldo pendiente de la factura (${$saldoPendiente})");
            }

            // Procesar archivo si se envió
            $rutaComprobante = null;
            if ($request->hasFile('comprobante')) {
                $archivo = $request->file('comprobante');
                $nombreArchivo = 'pago_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
                $rutaComprobante = $archivo->storeAs('comprobantes', $nombreArchivo, 'public');
            }

            // Crear pago
            $pago = Pago::create([
                'factura_id' => $request->factura_id,
                'monto' => $request->monto,
                'tipo_pago' => $request->tipo_pago,
                'referencia' => $request->referencia,
                'observaciones' => $request->observaciones,
                'comprobante_path' => $rutaComprobante,
                'estado' => 'pendiente',
                'created_by' => $user->id
            ]);

            // Verificar si este pago completa el total de la factura
            $nuevoTotalPagado = $totalPagado + $request->monto;
            if ($nuevoTotalPagado >= $factura->total && $pago->estado === 'aprobado') {
                $factura->update(['estado' => 'pagada']);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $pago->load(['factura.cliente:id,nombre,email']);

            // Registrar auditoría
            registrarAuditoria('create', 'Pago creado por $' . number_format($request->monto, 2) . ' para factura: ' . $factura->numero_factura, 'pagos');

            return response()->json([
                'success' => true,
                'data' => $pago->makeHidden(['comprobante_path']),
                'message' => 'Pago registrado exitosamente y está pendiente de validación'
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
            Log::error('Error al crear pago: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data' => $request->except(['comprobante']),
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
                    'message' => 'ID de pago inválido',
                    'error_code' => 'INVALID_PAYMENT_ID'
                ], 400);
            }

            $pago = Pago::with([
                'factura.cliente:id,nombre,email,telefono',
                'factura:id,numero_factura,total,estado',
                'validadoPor:id,name,email'
            ])->find($id);

            if (!$pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                    'error_code' => 'PAYMENT_NOT_FOUND'
                ], 404);
            }

            // Verificar permisos
            $user = $request->user();
            if (!$user->roles->whereIn('nombre', ['Administrador', 'Pagos'])->count()) {
                if ($user->roles->where('nombre', 'Cliente')->count() && $user->id !== $pago->factura->cliente_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver este pago',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                } elseif (!$user->roles->where('nombre', 'Cliente')->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver pagos',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                }
            }

            // Ocultar path del comprobante por seguridad
            $pagoData = $pago->makeHidden(['comprobante_path']);

            return response()->json([
                'success' => true,
                'data' => $pagoData,
                'message' => 'Pago obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener pago: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'pago_id' => $id,
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
     * Aprobar un pago
     */
    public function aprobar(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de pago inválido',
                    'error_code' => 'INVALID_PAYMENT_ID'
                ], 400);
            }

            // Solo usuarios con rol Pagos o Administrador pueden aprobar
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Pagos'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para aprobar pagos',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $pago = Pago::with('factura')->find($id);

            if (!$pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                    'error_code' => 'PAYMENT_NOT_FOUND'
                ], 404);
            }

            if ($pago->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar pagos pendientes',
                    'error_code' => 'PAYMENT_NOT_PENDING'
                ], 409);
            }

            // Validar observaciones si se proporcionan
            $request->validate([
                'observaciones_validacion' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Aprobar pago
            $pago->update([
                'estado' => 'aprobado',
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
                'observaciones_validacion' => $request->input('observaciones_validacion')
            ]);

            // Verificar si este pago completa el total de la factura
            $totalPagado = $pago->factura->pagos()->where('estado', 'aprobado')->sum('monto');
            if ($totalPagado >= $pago->factura->total) {
                $pago->factura->update(['estado' => 'pagada']);
            }

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('approve_payment', 'Pago aprobado por $' . number_format($pago->monto, 2) . ' para factura: ' . $pago->factura->numero_factura, 'pagos');

            return response()->json([
                'success' => true,
                'message' => 'Pago aprobado exitosamente'
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
            Log::error('Error al aprobar pago: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'pago_id' => $id,
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
     * Rechazar un pago
     */
    public function rechazar(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de pago inválido',
                    'error_code' => 'INVALID_PAYMENT_ID'
                ], 400);
            }

            // Solo usuarios con rol Pagos o Administrador pueden rechazar
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Pagos'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para rechazar pagos',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $pago = Pago::with('factura')->find($id);

            if (!$pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                    'error_code' => 'PAYMENT_NOT_FOUND'
                ], 404);
            }

            if ($pago->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar pagos pendientes',
                    'error_code' => 'PAYMENT_NOT_PENDING'
                ], 409);
            }

            // Validar observaciones (obligatorias para rechazo)
            $request->validate([
                'observaciones_validacion' => 'required|string|min:10|max:500'
            ]);

            DB::beginTransaction();

            // Rechazar pago
            $pago->update([
                'estado' => 'rechazado',
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
                'observaciones_validacion' => $request->input('observaciones_validacion')
            ]);

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('reject_payment', 'Pago rechazado por $' . number_format($pago->monto, 2) . ' para factura: ' . $pago->factura->numero_factura . '. Motivo: ' . $request->input('observaciones_validacion'), 'pagos');

            return response()->json([
                'success' => true,
                'message' => 'Pago rechazado exitosamente'
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
            Log::error('Error al rechazar pago: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'pago_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR'
            ], 500);
        }
    }
}
