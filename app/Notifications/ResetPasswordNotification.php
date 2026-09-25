<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class ResetPasswordNotification extends ResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(string $token, public string $email)
    {
        parent::__construct($token);
        $this->afterCommit();
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return $notifiable->isActive()
            && $notifiable->email === $this->email
            && Password::tokenExists($notifiable, $this->token);
    }

    protected function buildMailMessage(mixed $url): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefina sua senha — TheMerchant')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->action('Redefinir minha senha', $url)
            ->line('Este link expira em 60 minutos e só pode ser usado uma vez.')
            ->line('Se você não solicitou a alteração, ignore esta mensagem.');
    }
}
