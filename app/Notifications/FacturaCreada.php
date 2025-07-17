<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FacturaCreada extends Notification
{
    use Queueable;

    protected $factura;

    public function __construct($factura)
    {
        $this->factura = $factura;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $pdf = Pdf::loadView('facturas.pdf', ['factura' => $this->factura]);
        $pdfContent = $pdf->output();

        return (new MailMessage)
            ->subject('Factura #' . $this->factura->id)
            ->greeting('Hola ' . $this->factura->cliente->nombre . ',')
            ->line('Adjunto encontrarás la factura de tu compra.')
            ->attachData($pdfContent, 'factura_'.$this->factura->id.'.pdf', [
                'mime' => 'application/pdf',
            ])
            ->line('Gracias por tu preferencia.');
    }

}
