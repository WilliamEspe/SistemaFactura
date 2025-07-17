<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FacturaNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $factura;

    public function __construct(Factura $factura)
    {
        $this->factura = $factura;
    }

    public function build()
    {
        $pdf = Pdf::loadView('facturas.pdf', ['factura' => $this->factura]);
        $nombreArchivo = 'Factura_' . $this->factura->id . '.pdf';

        return $this->subject('Factura electrónica')
            ->markdown('emails.factura')
            ->with(['factura' => $this->factura])
            ->attachData($pdf->output(), $nombreArchivo);
    }
}
