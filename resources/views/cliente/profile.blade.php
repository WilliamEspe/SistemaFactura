@extends('layouts.app')

@section('title', 'Mi Perfil - Cliente')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Mi Perfil</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Información del Cliente -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Información Personal</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $cliente->nombre }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $cliente->email }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $cliente->telefono ?? 'No especificado' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dirección</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $cliente->direccion ?? 'No especificada' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente desde</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $cliente->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Cambiar Contraseña -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Cambiar Contraseña</h2>
                
                @if($user->created_at->eq($user->updated_at))
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                        <p class="font-medium">Contraseña predeterminada activa</p>
                        <p class="text-sm">Se recomienda cambiar la contraseña predeterminada (password123) por seguridad.</p>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('cliente.actualizar.password') }}">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Contraseña Actual</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                   required>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                            <input type="password" id="password" name="password" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                   required>
                            <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres</p>
                        </div>
                        
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                   required>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Facturas del Cliente -->
        <div class="bg-white p-6 rounded-lg shadow-md mt-8">
            <h2 class="text-xl font-semibold mb-4">Mis Facturas</h2>
            
            @if($cliente->facturas->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($cliente->facturas->take(10) as $factura)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ $factura->numero_factura }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $factura->fecha_emision->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ${{ number_format($factura->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $factura->estado === 'pagada' ? 'bg-green-100 text-green-800' : 
                                               ($factura->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($factura->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($cliente->facturas->count() > 10)
                    <p class="mt-4 text-sm text-gray-500">Mostrando las últimas 10 facturas de {{ $cliente->facturas->count() }} totales</p>
                @endif
            @else
                <p class="text-gray-500">No tienes facturas registradas.</p>
            @endif
        </div>
    </div>
</div>
@endsection
