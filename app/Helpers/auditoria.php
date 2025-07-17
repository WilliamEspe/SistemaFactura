<?php

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($accion, $descripcion, $modulo)
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'modulo' => $modulo,
        ]);
    }
}
