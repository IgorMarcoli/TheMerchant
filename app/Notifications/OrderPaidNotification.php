<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pagamento Aprovado — Pedido #{$this->order->order_number}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O pagamento do seu pedido #{$this->order->order_number} foi confirmado com sucesso.")
            ->line("Valor total: R$ " . number_format($this->order->total_amount, 2, ',', '.'))
            ->action('Ver Detalhes do Pedido', route('orders.show', $this->order))
            ->line('O vendedor já foi notificado para realizar a liberação do seu item/serviço digital.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'amount'       => $this->order->total_amount,
            'message'      => 'Pagamento confirmado com sucesso.',
        ];
    }
}
