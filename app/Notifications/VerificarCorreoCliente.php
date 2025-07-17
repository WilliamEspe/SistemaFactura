<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerificarCorreoCliente extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('clientes.verificar', ['cliente' => $notifiable->id, 'hash' => sha1($notifiable->email)]);

        return (new MailMessage)
            ->subject('Verifica tu correo')
            ->greeting("Hola {$notifiable->nombre},")
            ->line('Por favor confirma tu correo electrónico para poder recibir facturas.')
            ->action('Confirmar correo', $url)
            ->line('Gracias por registrarte.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
