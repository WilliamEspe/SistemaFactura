<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\FacturaCreada;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\FacturaNotificacion;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with(['cliente', 'detalles.producto', 'pagos']);

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('cliente', function($q) use ($search) {
                      $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Aplicar filtro por estado si existe
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Aplicar filtro por fecha si existe
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortBy, ['id', 'total', 'estado', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $facturas = $query->paginate(15)->appends($request->query());
        
        return view('facturas.index', [
            'facturas' => $facturas,
            'roles' => Auth::user()->roles->pluck('nombre'),
            'usuarioId' => Auth::id(),
        ]);
    }

    public function create()
    {
        $clientes = Cliente::all();
        $productos = Producto::all();
        return view('facturas.create', compact('clientes', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            /** @var Factura $factura */
            $factura = Factura::create([
                'cliente_id' => $request->input('cliente_id'),
                'user_id' => Auth::id(),
                'created_by' => Auth::id(),
                'estado' => 'pendiente_pago', // Cambio aquí: de 'pendiente' a 'pendiente_pago'
                'total' => 0,
            ]);

            $total = 0;
            foreach ($request->input('productos', []) as $item) {
                /** @var Producto $producto */
                $producto = Producto::findOrFail($item['producto_id']);

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}");
                }

                $subtotal = $producto->precio * $item['cantidad'];
                FacturaDetalle::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                ]);

                $producto->decrement('stock', $item['cantidad']);
                $total += $subtotal;
            }

            $factura->update(['total' => $total]);

            $factura->load('cliente');

            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Creación de factura',
                'descripcion' => "Factura #{$factura->id} por \${$factura->total} para cliente {$factura->cliente->nombre}",
                'modulo' => 'Facturas'
            ]);

            DB::commit();
            return redirect()->route('facturas.index')->with('success', 'Factura creada correctamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['msg' => $e->getMessage()]);
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

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    /**
     * @param Factura $factura
     */
    public function anular(Factura $factura)
    {
        if ($factura->anulada) {
            return back()->withErrors(['msg' => 'La factura ya fue anulada.']);
        }

        DB::beginTransaction();
        try {
            foreach ($factura->detalles as $detalle) {
                /** @var FacturaDetalle $detalle */
                $detalle->producto->increment('stock', $detalle->cantidad);
            }

            $factura->update(['anulada' => true]);

            Auditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'Anulación de factura',
                'descripcion' => "Factura #{$factura->id} anulada y stock restaurado.",
                'modulo' => 'Facturas'
            ]);

            DB::commit();
            return back()->with('success', 'Factura anulada y stock restaurado.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['msg' => 'Error al anular factura.']);
        }
    }

    /**
     * @param Factura $factura
     */
    public function descargarPDF(Factura $factura)
    {
        $factura->load('cliente', 'detalles.producto');
        $pdf = Pdf::loadView('facturas.pdf', compact('factura'));
        return $pdf->download('factura_' . $factura->id . '.pdf');
    }

    /**
     * @param Factura $factura
     */
    public function enviarPDF(Factura $factura)
    {
        $factura->load('cliente', 'detalles.producto');

        if (!$factura->cliente->email) {
            return back()->withErrors(['msg' => 'El cliente no tiene un correo registrado.']);
        }

        $factura->cliente->notify(new FacturaCreada($factura));

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Envío de PDF',
            'descripcion' => "Factura #{$factura->id} enviada a {$factura->cliente->email}",
            'modulo' => 'Facturas'
        ]);

        return back()->with('success', 'Factura enviada a: ' . $factura->cliente->email);
    }

    /**
     * @param Factura $factura
     */
    public function notificar(Factura $factura)
    {
        /** @var Cliente $cliente */
        $cliente = $factura->cliente;

        if (!$cliente->email_verified_at) {
            return back()->withErrors(['msg' => 'El correo del cliente no ha sido verificado.']);
        }

        Mail::to($cliente->email)->send(new FacturaNotificacion($factura));

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Notificación enviada',
            'descripcion' => "Factura #{$factura->id} notificada al correo {$cliente->email}",
            'modulo' => 'Facturas'
        ]);

        return back()->with('success', 'Factura notificada al correo: ' . $cliente->email);
    }
}
