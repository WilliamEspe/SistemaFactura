@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Estado de Autenticación</h2>
        
        @auth
            <div class="space-y-4">
                <p><strong>Usuario:</strong> {{ auth()->user()->name }}</p>
                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                <p><strong>ID:</strong> {{ auth()->user()->id }}</p>
                <p><strong>Rol:</strong> 
                    @if(auth()->user()->role)
                        {{ auth()->user()->role->name }}
                    @else
                        <span class="text-red-500">Sin rol asignado</span>
                    @endif
                </p>
                
                @if(auth()->user()->role && auth()->user()->role->name === 'Cliente')
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        ✅ Tienes acceso a las funciones de cliente
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('cliente.facturas') }}" 
                           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                            Ver Mis Facturas
                        </a>
                    </div>
                @else
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        ❌ No tienes rol de Cliente. No puedes acceder a las funciones de pago.
                    </div>
                @endif
            </div>
        @else
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                ⚠️ No estás autenticado. Por favor inicia sesión.
            </div>
            
            <div class="mt-4">
                <a href="{{ route('login') }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Iniciar Sesión
                </a>
            </div>
        @endauth
    </div>
</div>
@endsection
