<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        protected PaymentGatewayService $paymentGateway
    ) {}

    /**
     * Converte o carrinho do usuário em um pedido imutável e inicia o checkout no gateway.
     */
    public function checkout(User $user, ?string $notes = null): array
    {
        $cart = Cart::with(['items.listing'])->where('user_id', $user->id)->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw new RuntimeException('O carrinho está vazio.');
        }

        return DB::transaction(function () use ($cart, $user, $notes) {
            $total = 0;

            // Validação de disponibilidade de cada anúncio
            foreach ($cart->items as $item) {
                if (! $item->listing || ! $item->listing->isAvailable()) {
                    throw new RuntimeException("O anúncio '{$item->listing?->title}' não está mais disponível.");
                }
                $total += $item->unit_price * $item->quantity;
            }

            // Criação do pedido consolidado (imutabilidade contábil)
            $order = Order::create([
                'order_number' => 'ORD-'.strtoupper(Str::random(8)).'-'.date('Ymd'),
                'buyer_id' => $user->id,
                'total_amount' => $total,
                'status' => 'pendente',
                'notes' => $notes,
            ]);

            // Criação dos itens do pedido preservando os valores históricos
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'listing_id' => $item->listing_id,
                    'seller_id' => $item->listing->seller_id,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'delivery_status' => 'aguardando_pagamento',
                ]);

                // Atualiza anúncio para vendido se for item único
                $item->listing->update(['status' => 'vendido']);
            }

            // Esvazia os itens do carrinho do usuário
            $cart->items()->delete();

            // Gera link de pagamento via camada desacoplada de gateway
            $preference = $this->paymentGateway->createPaymentPreference($order);

            return [
                'order' => $order,
                'checkout_url' => $preference['checkout_url'] ?? route('checkout.success', $order),
                'preference_id' => $preference['id'] ?? null,
            ];
        });
    }
}
