<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Factura;
use App\Notifications\VerificarCorreoCliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::all();
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email',
            'telefono' => 'required|string|max:15',
            'direccion' => 'required|string|max:255',
        ]);

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Registrar Cliente',
            'descripcion' => "Cliente {$request->nombre} registrado.",
            'modulo' => 'Clientes',
        ]);

        $cliente = Cliente::create($request->only('nombre', 'email', 'telefono', 'direccion'));
        $cliente->notify(new VerificarCorreoCliente());

        return back()->with('success', 'Cliente registrado correctamente. Se envió un correo de verificación.');
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

        $cliente->motivo_eliminacion = $request->motivo_eliminacion;
        $cliente->delete();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Eliminar Cliente',
            'descripcion' => "Cliente {$cliente->nombre} movido a papelera. Motivo: {$request->motivo_eliminacion}",
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

    public function papelera()
    {
        $clientes = Cliente::onlyTrashed()->paginate(10);
        return view('clientes.papelera', compact('clientes'));
    }
}
