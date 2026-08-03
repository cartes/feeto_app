<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Código y Enlace para Restablecer tu Contraseña - Feeto')
            ->greeting('¡Hola '.($notifiable->name ?? 'Usuario').'!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en Feeto.')
            ->line('Tu código de verificación / token de recuperación es:')
            ->line('**'.$this->token.'**')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace y código de recuperación expiran en '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60).' minutos.')
            ->line('Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.');
    }
}
