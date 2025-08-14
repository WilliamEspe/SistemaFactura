<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();
        $roles = $usuario->roles->pluck('nombre');

        $totalFacturas = Factura::count();
        $totalVentas = Factura::where('anulada', false)->sum('total');
        $productosBajoStock = Producto::where('stock', '<=', 5)->count();
        $totalUsuarios = User::count();

        // Agrupar ventas por mes (últimos 6 meses)
        $ventasMensuales = Factura::selectRaw("TO_CHAR(created_at, 'Mon') AS mes, SUM(total) as total")
            ->where('anulada', false)
            ->groupByRaw("TO_CHAR(created_at, 'Mon'), DATE_TRUNC('month', created_at)")
            ->orderByRaw("DATE_TRUNC('month', created_at)")
            ->limit(6)
            ->get();

        return view('home', compact(
            'usuario',
            'roles',
            'totalFacturas',
            'totalVentas',
            'productosBajoStock',
            'totalUsuarios',
            'ventasMensuales'
        ));
    }
}
