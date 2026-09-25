<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public string $email)
    {
        $this->afterCommit();
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return $notifiable->isActive()
            && ! $notifiable->hasVerifiedEmail()
            && $notifiable->email === $this->email;
    }

    protected function buildMailMessage(mixed $url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail — TheMerchant')
            ->greeting('Olá!')
            ->line('Confirme seu endereço de e-mail para sua conta no TheMerchant.')
            ->action('Confirmar meu e-mail', $url)
            ->line('Este link expira em 60 minutos. Entre na conta correspondente para confirmar.')
            ->line('Se você não criou esta conta, ignore esta mensagem.');
    }
}
