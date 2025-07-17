<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuarios = User::with('roles')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usuarios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'activo' => true,
        ]);

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Crear Usuario',
            'descripcion' => "Usuario {$request->name} creado.",
            'modulo' => 'Usuarios',
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
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
    public function edit(User $usuario)
    {
        return view('usuarios.edit', compact('usuario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'password' => 'nullable|min:6',
        ]);

        $usuario->motivo_bloqueo = $request->motivo_bloqueo;

        $usuario->name = $request->name;
        $usuario->email = $request->email;
        if ($request->filled('password')) {
            $usuario->forceFill([
                'password' => Hash::make($request->password)
            ]);
        }
        $usuario->save();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Actualizar Usuario',
            'descripcion' => "Usuario {$usuario->name} actualizado.",
            'modulo' => 'Usuarios',
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Eliminar Usuario',
            'descripcion' => "Usuario {$usuario->name} eliminado.",
            'modulo' => 'Usuarios',
        ]);

        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado.');
    }

    public function asignarRol(Request $request, User $usuario)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Obtener los nombres de los nuevos roles
        $rolesAsignados = Role::whereIn('id', $request->roles)->pluck('nombre')->toArray();

        // Sincronizar roles
        $usuario->roles()->sync($request->roles);

        // Enviar notificación por correo
        try {
            Notification::send($usuario, new \App\Notifications\RolAsignado(implode(', ', $rolesAsignados)));
        } catch (\Exception $e) {
            Log::error("Error al enviar notificación de rol: " . $e->getMessage());
        }

        // Registrar en auditoría
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Asignación de Rol',
            'descripcion' => "Se asignaron los roles [" . implode(', ', $rolesAsignados) . "] al usuario {$usuario->name}.",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Roles actualizados y notificación enviada.');
    }

    public function toggleEstado(User $usuario)
    {
        $nuevoEstado = !$usuario->activo;
        $usuario->activo = $nuevoEstado;

        if ($nuevoEstado) {
            $usuario->motivo_bloqueo = null; // Limpiar motivo al reactivar
        }

        $usuario->save();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => $nuevoEstado ? 'Activar Usuario' : 'Desactivar Usuario',
            'descripcion' => "Se cambió el estado de {$usuario->name} a " . ($nuevoEstado ? 'Activo' : 'Inactivo'),
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }

    public function inactivar(Request $request, User $usuario)
    {
        $request->validate([
            'motivo_bloqueo' => 'required|string|max:255',
        ]);

        $usuario->activo = false;
        $usuario->motivo_bloqueo = $request->motivo_bloqueo;
        $usuario->save();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Inactivar Usuario',
            'descripcion' => "Usuario {$usuario->name} fue inactivado. Motivo: {$request->motivo_bloqueo}",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Usuario inactivado correctamente.');
    }

    public function eliminar(Request $request, User $usuario)
    {
        $request->validate([
            'motivo_eliminacion' => 'required|string|max:255',
            'confirmacion' => 'accepted'
        ]);

        $usuario->motivo_eliminacion = $request->motivo_eliminacion;
        $usuario->delete();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Eliminar Usuario',
            'descripcion' => "Usuario {$usuario->name} movido a papelera. Motivo: {$request->motivo_eliminacion}",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Usuario movido a papelera.');
    }
    public function restaurar(Request $request, $id)
    {
        $request->validate([
            'motivo_restauracion' => 'required|string|max:255',
        ]);

        $usuario = User::onlyTrashed()->findOrFail($id);
        $usuario->restore();

        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Restaurar Usuario',
            'descripcion' => "Usuario {$usuario->name} restaurado. Motivo: {$request->motivo_restauracion}",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Usuario restaurado correctamente.');
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

        $usuario = User::onlyTrashed()->findOrFail($id);
        $nombre = $usuario->name;
        $usuario->forceDelete();

        Auditoria::create([
            'user_id' => $usuarioAuth->id,
            'accion' => 'Eliminación definitiva de usuario',
            'descripcion' => "El usuario {$nombre} fue eliminado permanentemente desde la papelera.",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Usuario eliminado permanentemente.');
    }
    public function papelera()
    {
        $usuarios = User::onlyTrashed()->get();
        return view('usuarios.papelera', compact('usuarios'));
    }

    public function auditoria()
    {
        $logs = Auditoria::latest()->paginate(10);
        return view('auditoria.index', compact('logs'));
    }
}
