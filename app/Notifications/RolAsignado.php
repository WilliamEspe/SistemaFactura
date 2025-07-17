<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RolAsignado extends Notification
{
    use Queueable;

    protected $rol;

    public function __construct($rol)
    {
        $this->rol = $rol;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nuevo rol asignado')
            ->greeting("Hola {$notifiable->name},")
            ->line("Se te ha asignado el rol de **{$this->rol}**.")
            ->line('Ahora tienes acceso a nuevas funciones del sistema.')
            ->salutation('Saludos, Sistema de Facturación Segura');
    }
}

