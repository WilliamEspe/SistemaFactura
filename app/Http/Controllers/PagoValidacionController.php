<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Factura;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class PagoValidacionController extends BaseController
{
    /**
     * Constructor - aplicar middleware de roles
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Mostrar lista de pagos pendientes
     */
    public function index()
    {
        try {
            $user = Auth::user();

            // Verificar permisos usando el nombre correcto del rol
            if (!$user->hasRole(['Pagos', 'Administrador'])) {
                abort(403, 'No tienes permisos para acceder a esta sección. Necesitas el rol de Pagos o Administrador.');
            }

            $pagosPendientes = Pago::with(['factura.cliente', 'cliente'])
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'asc')
                ->get();

            return view('pagos.validacion.index', compact('pagosPendientes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar pagos pendientes', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al cargar los pagos pendientes']);
        }
    }

    /**
     * Mostrar detalles de un pago para validación
     */
    public function show(Pago $pago)
    {
        try {
            if (!Auth::user()->hasRole(['Pagos', 'Administrador'])) {
                abort(403, 'No tienes permisos para acceder a esta sección');
            }

            $pago->load(['factura.cliente', 'cliente', 'validador']);

            return view('pagos.validacion.show', compact('pago'));
        } catch (\Exception $e) {
            Log::error('Error al cargar detalles de pago', [
                'usuario_id' => Auth::id(),
                'pago_id' => $pago->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al cargar los detalles del pago']);
        }
    }

    /**
     * Aprobar un pago
     */
    public function aprobar(Request $request, Pago $pago)
    {
        try {
            // Agregar verificación de roles al método aprobar
            if (!Auth::user()->hasRole(['Pagos', 'Administrador'])) {
                abort(403, 'No tienes permisos para realizar esta acción');
            }

            $request->validate([
                'observaciones_validacion' => 'nullable|string|max:1000'
            ]);

            // Verificar que el pago esté pendiente
            if ($pago->estado !== 'pendiente') {
                return back()->withErrors(['error' => 'Este pago ya fue procesado']);
            }

            DB::beginTransaction();

            // Actualizar el pago
            $pago->update([
                'estado' => 'aprobado',
                'validado_por' => Auth::id(),
                'validated_at' => now(),
                'observaciones' => $pago->observaciones .
                    ($request->observaciones_validacion ?
                        "\n\n--- VALIDACIÓN ---\n" . $request->observaciones_validacion : '')
            ]);

            // Actualizar estado de la factura
            $factura = $pago->factura;
            $factura->update(['estado' => 'pagada']);

            // Registrar auditoría
            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Aprobación de pago',
                'descripcion' => "Pago #{$pago->id} aprobado para factura #{$factura->id}. Factura marcada como pagada.",
                'modulo' => 'Validación de Pagos'
            ]);

            DB::commit();

            Log::info('Pago aprobado', [
                'pago_id' => $pago->id,
                'factura_id' => $factura->id,
                'validado_por' => Auth::id(),
                'monto' => $pago->monto
            ]);

            return redirect()->route('pagos.validacion.index')
                ->with('success', "Pago #{$pago->id} aprobado exitosamente. La factura ha sido marcada como pagada.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al aprobar pago', [
                'usuario_id' => Auth::id(),
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Error interno al aprobar el pago']);
        }
    }

    /**
     * Rechazar un pago
     */
    public function rechazar(Request $request, Pago $pago)
    {
        try {
            if (!Auth::user()->hasRole(['Pagos', 'Administrador'])) {
                abort(403, 'No tienes permisos para acceder a esta sección');
            }
            $request->validate([
                'motivo_rechazo' => 'required|string|max:1000'
            ]);

            // Verificar que el pago esté pendiente
            if ($pago->estado !== 'pendiente') {
                return back()->withErrors(['error' => 'Este pago ya fue procesado']);
            }

            DB::beginTransaction();

            // Actualizar el pago
            $pago->update([
                'estado' => 'rechazado',
                'validado_por' => Auth::id(),
                'validated_at' => now(),
                'observaciones' => $pago->observaciones .
                    "\n\n--- RECHAZO ---\n" . $request->motivo_rechazo
            ]);

            // La factura mantiene su estado pendiente (no se cambia)

            // Registrar auditoría
            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Rechazo de pago',
                'descripcion' => "Pago #{$pago->id} rechazado para factura #{$pago->factura->id}. Motivo: {$request->motivo_rechazo}",
                'modulo' => 'Validación de Pagos'
            ]);

            DB::commit();

            Log::warning('Pago rechazado', [
                'pago_id' => $pago->id,
                'factura_id' => $pago->factura->id,
                'validado_por' => Auth::id(),
                'motivo' => $request->motivo_rechazo,
                'monto' => $pago->monto
            ]);

            return redirect()->route('pagos.validacion.index')
                ->with('success', "Pago #{$pago->id} rechazado. La factura permanece pendiente de pago.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al rechazar pago', [
                'usuario_id' => Auth::id(),
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Error interno al rechazar el pago']);
        }
    }

    /**
     * Historial de pagos validados
     */
    public function historial(Request $request)
    {
        try {
            if (!Auth::user()->hasRole(['Pagos', 'Administrador'])) {
                abort(403, 'No tienes permisos para acceder a esta sección');
            }
            $query = Pago::with(['factura.cliente', 'cliente', 'validador'])
                ->whereIn('estado', ['aprobado', 'rechazado']);

            // Filtros opcionales
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo_pago')) {
                $query->where('tipo_pago', $request->tipo_pago);
            }

            if ($request->filled('fecha_desde')) {
                $query->where(function($q) use ($request) {
                    $q->whereDate('validated_at', '>=', $request->fecha_desde)
                      ->orWhere(function($subq) use ($request) {
                          $subq->whereNull('validated_at')
                               ->whereDate('updated_at', '>=', $request->fecha_desde);
                      });
                });
            }

            if ($request->filled('fecha_hasta')) {
                $query->where(function($q) use ($request) {
                    $q->whereDate('validated_at', '<=', $request->fecha_hasta)
                      ->orWhere(function($subq) use ($request) {
                          $subq->whereNull('validated_at')
                               ->whereDate('updated_at', '<=', $request->fecha_hasta);
                      });
                });
            }

            // Ordenar por fecha de validación o actualización
            $pagosValidados = $query->orderByRaw('COALESCE(validated_at, updated_at) DESC')->paginate(15);

            return view('pagos.validacion.historial', compact('pagosValidados'));
        } catch (\Exception $e) {
            Log::error('Error al cargar historial de pagos', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al cargar el historial de pagos']);
        }
    }
}
