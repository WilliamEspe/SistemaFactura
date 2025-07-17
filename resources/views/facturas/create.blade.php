@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-8">
    <h2 class="text-2xl font-bold mb-6">Nueva Factura</h2>
    <div class="flex justify-between items-center mb-4">
            <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Volver al Dashboard
            </a>
        </div>

    @if($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ $errors->first('msg') ?? 'Revise los campos del formulario.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('facturas.store') }}">
        @csrf

        <!-- Selección de cliente -->
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Cliente:</label>
            <select name="cliente_id" class="w-full border px-3 py-2 rounded" required>
                <option value="">Seleccione un cliente</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}">{{ $cliente->nombre }} - {{ $cliente->email }}</option>
                @endforeach
            </select>
        </div>

        <!-- Tabla de productos -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Productos:</h3>
            <table class="w-full text-sm table-auto border mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 py-1">Producto</th>
                        <th class="px-2 py-1">Cantidad</th>
                        <th class="px-2 py-1">Precio</th>
                        <th class="px-2 py-1">Subtotal</th>
                        <th class="px-2 py-1">Acción</th>
                    </tr>
                </thead>
                <tbody id="productos-container">
                    <!-- Se agregarán filas dinámicamente -->
                </tbody>
            </table>

            <button type="button" onclick="agregarProducto()" class="bg-green-600 text-white px-4 py-2 rounded">
                + Agregar Producto
            </button>
        </div>

        <!-- Total -->
        <div class="text-right text-lg font-semibold mt-4">
            Total: $<span id="total">0.00</span>
        </div>

        <!-- Botón de guardar -->
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">
                Guardar Factura
            </button>
        </div>
    </form>
</div>

<!-- Plantilla JS -->
<script>
    const productosData = @json($productos);
    let productosSeleccionados = [];

    function agregarProducto() {
        const index = productosSeleccionados.length;
        const container = document.getElementById('productos-container');

        let row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-2 py-1">
                <select name="productos[${index}][producto_id]" class="border rounded px-2 py-1 w-full" onchange="actualizarPrecio(this, ${index})" required>
                    <option value="">Seleccione</option>
                    ${productosData.map(p => `<option value="${p.id}" data-precio="${p.precio}">${p.nombre}</option>`).join('')}
                </select>
            </td>
            <td class="px-2 py-1">
                <input type="number" name="productos[${index}][cantidad]" min="1" value="1" class="border rounded px-2 py-1 w-full" onchange="recalcularSubtotal(${index})" required>
            </td>
            <td class="px-2 py-1">
                <input type="text" id="precio-${index}" class="border rounded px-2 py-1 w-full bg-gray-100" disabled>
            </td>
            <td class="px-2 py-1">
                <input type="text" name="productos[${index}][subtotal]" id="subtotal-${index}" class="border rounded px-2 py-1 w-full bg-gray-100" readonly>
            </td>
            <td class="px-2 py-1 text-center">
                <button type="button" onclick="eliminarFila(this)" class="text-red-600">Eliminar</button>
            </td>
        `;

        container.appendChild(row);
        productosSeleccionados.push({});
    }

    function actualizarPrecio(select, index) {
        const selectedOption = select.options[select.selectedIndex];
        const precio = selectedOption.getAttribute('data-precio');
        document.getElementById(`precio-${index}`).value = parseFloat(precio).toFixed(2);
        recalcularSubtotal(index);
    }

    function recalcularSubtotal(index) {
        const cantidad = document.querySelector(`[name="productos[${index}][cantidad]"]`).value;
        const precio = document.getElementById(`precio-${index}`).value;
        const subtotal = cantidad * precio;
        document.getElementById(`subtotal-${index}`).value = subtotal.toFixed(2);
        recalcularTotal();
    }

    function recalcularTotal() {
        let total = 0;
        productosSeleccionados.forEach((_, i) => {
            const input = document.getElementById(`subtotal-${i}`);
            if (input) total += parseFloat(input.value) || 0;
        });
        document.getElementById('total').textContent = total.toFixed(2);
    }

    function eliminarFila(button) {
        button.closest('tr').remove();
        recalcularTotal();
    }
</script>
@endsection
