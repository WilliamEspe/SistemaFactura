<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Si es una petición de API, devolver todos los productos
        if ($request->expectsJson() || $request->is('api/*')) {
            $productos = Producto::orderBy('nombre')->get();
            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos exitosamente',
                'data' => $productos
            ]);
        }
        
        // Para peticiones web, aplicar paginación y búsqueda
        $query = Producto::query();

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%")
                  ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        }

        // Aplicar ordenamiento
        $sortBy = $request->get('sort', 'nombre');
        $sortDirection = $request->get('direction', 'asc');
        
        if (in_array($sortBy, ['nombre', 'precio', 'stock', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('nombre', 'asc');
        }

        $productos = $query->paginate(15)->appends($request->query());
        
        return view('productos.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Crear Producto',
            'descripcion' => "Producto {$request->input('nombre')} creado.",
            'modulo' => 'Productos',
        ]);

        Producto::create($request->only('nombre', 'descripcion', 'precio', 'stock'));
        return back()->with('success', 'Producto registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Actualizar Producto',
            'descripcion' => "Producto {$producto->nombre} actualizado.",
            'modulo' => 'Productos',
        ]);

        $producto->update($request->only('nombre', 'descripcion', 'precio', 'stock'));
        return back()->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Eliminar Producto',
            'descripcion' => "Producto {$producto->nombre} eliminado.",
            'modulo' => 'Productos',
        ]);

        $producto->delete();
        return back()->with('success', 'Producto eliminado correctamente.');
    }
    
     public function eliminar(Request $request, Producto $producto)
            {
                $request->validate([
                    'motivo_eliminacion' => 'required|string|max:255',
                    'confirmacion' => 'accepted'
                ]);

                $producto->motivo_eliminacion = $request->input('motivo_eliminacion');
                $producto->delete();

                Auditoria::create([
                    'user_id' => Auth::id(),
                    'accion' => 'Eliminar Producto',
                    'descripcion' => "Producto {$producto->nombre} movido a papelera. Motivo: {$request->input('motivo_eliminacion')}",
                    'modulo' => 'Productos',
                ]);

                return back()->with('success', 'Producto movido a papelera.');
            }

        public function restaurar(Request $request, $id)
            {
                $request->validate([
                    'motivo_restauracion' => 'required|string|max:255',
                ]);

                /** @var Producto $producto */
                $producto = Producto::onlyTrashed()->findOrFail($id);
                $producto->restore();

                Auditoria::create([
                    'user_id' => Auth::id(),
                    'accion' => 'Restaurar Producto',
                    'descripcion' => "Producto {$producto->nombre} restaurado. Motivo: {$request->input('motivo_restauracion')}",
                    'modulo' => 'Productos',
                ]);

                return back()->with('success', 'Producto restaurado correctamente.');
            }

        public function eliminarDefinitivo(Request $request, $id)
            {
                $request->validate([
                    'password' => 'required|string',
                    'confirmacion' => 'accepted'
                ]);

                /** @var \App\Models\User $usuarioAuth */
                $usuarioAuth = Auth::user();

                if (!Hash::check($request->input('password'), $usuarioAuth->password)) {
                    return back()->withErrors(['password' => 'Contraseña incorrecta.']);
                }

                /** @var Producto $producto */
                $producto = Producto::onlyTrashed()->findOrFail($id);
                $nombre = $producto->nombre;
                $producto->forceDelete();

                Auditoria::create([
                    'user_id' => $usuarioAuth->id,
                    'accion' => 'Eliminación definitiva de producto',
                    'descripcion' => "El producto {$nombre} fue eliminado permanentemente desde la papelera.",
                    'modulo' => 'Productos',
                ]);

                return back()->with('success', 'Producto eliminado permanentemente.');
            }

            public function papelera(Request $request)
            {
                $query = Producto::onlyTrashed();

                // Aplicar búsqueda si existe
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                          ->orWhere('descripcion', 'LIKE', "%{$search}%")
                          ->orWhere('codigo', 'LIKE', "%{$search}%");
                    });
                }

                $productos = $query->paginate(15)->appends($request->query());
                return view('productos.papelera', compact('productos'));
            }

            public function auditoria(Request $request)
            {
                $query = Auditoria::where('modulo', 'Productos');

                // Aplicar búsqueda si existe
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('accion', 'LIKE', "%{$search}%")
                          ->orWhere('descripcion', 'LIKE', "%{$search}%");
                    });
                }

                $logs = $query->latest()->paginate(15)->appends($request->query());
                return view('auditoria.index', compact('logs'));
            }
    
    
}
