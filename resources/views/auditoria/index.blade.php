@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <h2 class="text-xl font-bold mb-4">Historial de Auditoría</h2>
    <div class="flex justify-between items-center mb-4">
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Dashboard
            </a>
        </div>

    <table class="w-full table-auto bg-white shadow-md rounded">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2">Fecha</th>
                <th class="p-2">Usuario</th>
                <th class="p-2">Acción</th>
                <th class="p-2">Módulo</th>
                <th class="p-2">Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr class="border-b">
                    <td class="p-2">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->usuario ? $log->usuario->name : 'Sistema' }}</td>
                    <td class="p-2">{{ $log->accion }}</td>
                    <td class="p-2">{{ $log->modulo }}</td>
                    <td class="p-2">{{ $log->descripcion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
