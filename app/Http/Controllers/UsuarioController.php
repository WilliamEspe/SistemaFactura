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
use Laravel\Sanctum\PersonalAccessToken;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'tokens' => function($query) {
            $query->where('name', 'LIKE', '%API%')->latest();
        }]);

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $usuarios = $query->paginate(10)->appends($request->query());
        
        // Obtener todos los tokens para la sección de tokens
        $tokens = PersonalAccessToken::with('tokenable')
            ->latest()
            ->get();
        
        return view('usuarios.index', compact('usuarios', 'tokens'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var string $viewName */
        $viewName = 'usuarios.create';
        return view($viewName);
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
        /** @var string $viewName */
        $viewName = 'usuarios.edit';
        return view($viewName, compact('usuario'));
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
        
        // Prevenir asignación del rol "Cliente" desde la gestión de usuarios
        if (in_array('Cliente', $rolesAsignados)) {
            return back()->withErrors([
                'roles' => 'El rol "Cliente" no puede ser asignado manualmente. Los clientes se crean automáticamente desde el módulo de Clientes.'
            ]);
        }

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
    public function papelera(Request $request)
    {
        $query = User::onlyTrashed();

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $usuarios = $query->paginate(15)->appends($request->query());
        return view('usuarios.papelera', compact('usuarios'));
    }

    public function auditoria(Request $request)
    {
        $query = Auditoria::query();

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('accion', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%")
                  ->orWhere('modulo', 'LIKE', "%{$search}%");
            });
        }

        // Aplicar filtro por módulo si existe
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        // Aplicar filtro por fecha si existe
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $logs = $query->with('user')->latest()->paginate(15)->appends($request->query());
        return view('auditoria.index', compact('logs'));
    }

    public function crearTokenAccesso(Request $request)
    {
        $request->validate([
            'usuario' => 'required|exists:users,id',
            'token_name' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($request->input('usuario'));
        $tokenName = $request->input('token_name');
        
        // Crear token
        $token = $user->createToken($tokenName);
        $plainTextToken = $token->plainTextToken;
        
        // Guardar el token completo en la nueva columna si existe
        try {
            $token->accessToken->update([
                'plain_text_token' => $plainTextToken
            ]);
        } catch (\Exception $e) {
            // Si la columna no existe, continuamos normalmente
            Log::info('Columna plain_text_token no disponible, continuando...');
        }

        // Registrar auditoría
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Crear Token Usuario API',
            'descripcion' => "Token '{$tokenName}' creado para usuario {$user->name} ({$user->email})",
            'modulo' => 'Usuarios',
        ]);
        
        // Log para debugging
        Log::info('Token API creado para usuario:', [
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'token_name' => $tokenName,
            'created_by' => Auth::user()->email
        ]);
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Token de acceso API creado correctamente.')
            ->with('nuevo_token', [
                'nombre' => $tokenName,
                'usuario' => $user->name,
                'email' => $user->email,
                'token' => $plainTextToken
            ]);
    }

    /**
     * Revocar token de un usuario
     */
    public function revocarToken(Request $request, $tokenId)
    {
        $token = \Laravel\Sanctum\PersonalAccessToken::findOrFail($tokenId);
        $usuario = $token->tokenable;

        // Verificar que el token pertenezca a un usuario (no cliente)
        if (!$usuario instanceof User) {
            return back()->withErrors(['error' => 'Token no válido']);
        }

        $tokenName = $token->name;
        $token->delete();

        // Registrar auditoría
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => 'Revocar Token Usuario API',
            'descripcion' => "Token '{$tokenName}' revocado para usuario {$usuario->name}",
            'modulo' => 'Usuarios',
        ]);

        return back()->with('success', 'Token revocado exitosamente');
    }
}
