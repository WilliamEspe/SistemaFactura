@component('mail::message')
# Factura #{{ $factura->id }}

**Cliente:** {{ $factura->cliente->nombre }}  
**Total:** ${{ number_format($factura->total, 2) }}  
**Estado:** {{ $factura->anulada ? 'Anulada' : 'Válida' }}

@component('mail::button', ['url' => route('facturas.index')])
Ver Facturas
@endcomponent

Gracias por su compra.  
@endcomponent
