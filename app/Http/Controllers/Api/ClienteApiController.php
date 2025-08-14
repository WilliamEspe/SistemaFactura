<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Http\Requests\ClienteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

// Incluir helper de auditoría
require_once app_path('Helpers/auditoria.php');

/**
 * @group Clientes API
 * 
 * API endpoints para gestionar clientes
 */
class ClienteApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Verificar permisos
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver la lista de clientes',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Validar parámetros de entrada
            $request->validate([
                'search' => 'nullable|string|max:255',
                'estado' => 'nullable|in:activo,inactivo',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            $query = Cliente::query();

            // Filtros de búsqueda
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('telefono', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->filled('estado')) {
                if ($request->input('estado') === 'activo') {
                    $query->where('activo', true);
                } elseif ($request->input('estado') === 'inactivo') {
                    $query->where('activo', false);
                }
            }

            // Paginación
            $perPage = min($request->input('per_page', 15), 100); // Límite máximo de 100
            $clientes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Ocultar información sensible
            $clientes->getCollection()->transform(function ($cliente) {
                return $cliente->makeHidden(['password', 'remember_token', 'email_verified_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'message' => 'Clientes obtenidos exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al obtener clientes: ' . $e->getMessage(), [
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
                    'message' => 'No tienes permisos para crear clientes',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Validar datos de entrada
            $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|email|unique:clientes,email',
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:6|max:255'
            ]);

            DB::beginTransaction();

            $cliente = Cliente::create([
                'nombre' => trim($request->nombre),
                'email' => strtolower(trim($request->email)),
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'password' => Hash::make($request->password ?? 'cliente123'),
                'activo' => true,
                'created_by' => $request->user()->id
            ]);

            // Nota: Los roles son manejados a nivel de usuario, no de cliente

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('create', 'Cliente creado: ' . $cliente->nombre . ' (' . $cliente->email . ')', 'clientes');

            return response()->json([
                'success' => true,
                'data' => $cliente->makeHidden(['password', 'remember_token', 'email_verified_at']),
                'message' => 'Cliente creado exitosamente'
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
            Log::error('Error al crear cliente: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data' => $request->except(['password']),
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
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número para evitar inyección
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de cliente inválido',
                    'error_code' => 'INVALID_CLIENT_ID'
                ], 400);
            }

            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                    'error_code' => 'CLIENT_NOT_FOUND'
                ], 404);
            }

            // Verificar permisos: admin/ventas/secretario pueden ver cualquier cliente,
            // clientes solo pueden ver su propia información
            $user = $request->user();
            if (!$user->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                if ($user->id !== $cliente->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver este cliente',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                }
            }

            // Validar parámetros opcionales
            $request->validate([
                'include_facturas' => 'nullable|boolean',
                'include_statistics' => 'nullable|boolean'
            ]);

            $clienteData = $cliente->makeHidden(['password', 'remember_token', 'email_verified_at']);

            // Incluir estadísticas si se solicita
            if ($request->boolean('include_statistics')) {
                $facturas = $cliente->facturas()->where('anulada', false)->get();
                $clienteData['statistics'] = [
                    'total_facturas' => $facturas->count(),
                    'total_facturado' => $facturas->sum('total'),
                    'ultima_factura' => $facturas->max('created_at')
                ];
            }

            // Incluir facturas si se solicita
            if ($request->boolean('include_facturas')) {
                $facturas = $cliente->facturas()
                    ->select('id', 'numero_factura', 'total', 'estado', 'anulada', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();
                $clienteData['facturas'] = $facturas;
            }

            return response()->json([
                'success' => true,
                'data' => $clienteData,
                'message' => 'Cliente obtenido exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de consulta inválidos',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al obtener cliente: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'cliente_id' => $id,
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
                    'message' => 'ID de cliente inválido',
                    'error_code' => 'INVALID_CLIENT_ID'
                ], 400);
            }

            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                    'error_code' => 'CLIENT_NOT_FOUND'
                ], 404);
            }

            // Verificar permisos
            $user = $request->user();
            if (!$user->roles->whereIn('nombre', ['Administrador', 'Ventas', 'Secretario'])->count()) {
                if ($user->id !== $cliente->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para actualizar este cliente',
                        'error_code' => 'INSUFFICIENT_PERMISSIONS'
                    ], 403);
                }
            }

            // Validar datos de entrada
            $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('clientes')->ignore($cliente->id)
                ],
                'telefono' => 'sometimes|nullable|string|max:20',
                'direccion' => 'sometimes|nullable|string|max:500',
                'password' => 'sometimes|nullable|string|min:6|max:255'
            ]);

            DB::beginTransaction();

            $datosAnteriores = $cliente->only(['nombre', 'email', 'telefono', 'direccion']);

            // Actualizar solo los campos enviados
            $datosActualizar = [];
            if ($request->has('nombre')) {
                $datosActualizar['nombre'] = trim($request->nombre);
            }
            if ($request->has('email')) {
                $datosActualizar['email'] = strtolower(trim($request->email));
            }
            if ($request->has('telefono')) {
                $datosActualizar['telefono'] = $request->telefono;
            }
            if ($request->has('direccion')) {
                $datosActualizar['direccion'] = $request->direccion;
            }
            if ($request->filled('password')) {
                $datosActualizar['password'] = Hash::make($request->password);
            }

            $datosActualizar['updated_by'] = $user->id;

            $cliente->update($datosActualizar);

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('update', 'Cliente actualizado: ' . $cliente->nombre . ' (' . $cliente->email . ')', 'clientes');

            return response()->json([
                'success' => true,
                'data' => $cliente->makeHidden(['password', 'remember_token', 'email_verified_at']),
                'message' => 'Cliente actualizado exitosamente'
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
            Log::error('Error al actualizar cliente: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'cliente_id' => $id,
                'data' => $request->except(['password']),
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de cliente inválido',
                    'error_code' => 'INVALID_CLIENT_ID'
                ], 400);
            }

            // Solo administradores pueden eliminar
            if (!$request->user()->roles->where('nombre', 'Administrador')->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar clientes',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                    'error_code' => 'CLIENT_NOT_FOUND'
                ], 404);
            }

            // Verificar si tiene facturas asociadas
            if ($cliente->facturas()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el cliente porque tiene facturas asociadas',
                    'error_code' => 'CLIENT_HAS_INVOICES'
                ], 409);
            }

            DB::beginTransaction();

            // Soft delete
            $cliente->delete();

            DB::commit();

            // Registrar auditoría
            registrarAuditoria('delete', 'Cliente eliminado: ' . $cliente->nombre . ' (' . $cliente->email . ')', 'clientes');

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cliente: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'cliente_id' => $id,
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
