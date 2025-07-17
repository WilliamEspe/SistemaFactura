<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $factura->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        h1 { font-size: 20px; margin-bottom: 0; }
    </style>
</head>
<body>
    <h1>Factura #{{ $factura->id }}</h1>
    <p><strong>Cliente:</strong> {{ $factura->cliente->nombre }}</p>
    <p><strong>Total:</strong> ${{ number_format($factura->total, 2) }}</p>
    <p><strong>Estado:</strong> {{ $factura->anulada ? 'Anulada' : 'Válida' }}</p>

    <h3>Detalles:</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>P. Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td>{{ $detalle->cantidad }}</td>
                    <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td>${{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
