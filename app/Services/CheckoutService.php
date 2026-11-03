<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checkout transacional (RF10, RNF06) conforme o contrato de domínio #29:
 * reserva com expiração, snapshots imutáveis e cobrança fora da transação de banco.
 */
class CheckoutService
{
    private const MIN_UNIT_PRICE_CENTS = 100;

    public function __construct(
        private readonly PaymentGatewayService $payments,
    ) {
    }

    /**
     * Converte o carrinho em pedido pendente com reservas e inicia a cobrança PIX.
     * Repetir com a mesma chave devolve o mesmo pedido, sem nova reserva nem cobrança.
     *
     * @param  array<int|string, string|null>  $itemInstructions  instruções do comprador por id do item do carrinho
     */
    public function checkout(User $user, string $checkoutKey, ?string $notes = null, array $itemInstructions = []): Order
    {
        $existing = $this->findByKey($user, $checkoutKey);

        if ($existing !== null) {
            return $existing;
        }

        $this->syncChangedPrices($user);

        try {
            $order = DB::transaction(
                fn (): Order => $this->reserve($user, $checkoutKey, $notes, $itemInstructions),
                3
            );
        } catch (UniqueConstraintViolationException) {
            // Envio concorrente com a mesma chave: a outra requisição já criou o pedido.
            return $this->findByKey($user, $checkoutKey) ?? throw new CheckoutException('Não foi possível concluir o checkout. Tente novamente.');
        }

        try {
            $this->payments->startPayment($order);
        } catch (PaymentGatewayException $exception) {
            if ($exception->definitive) {
                $this->restoreCart($user, $order);

                throw new CheckoutException('O gateway recusou a criação do pagamento. Sua reserva foi liberada e os itens voltaram ao carrinho.');
            }

            // Resultado desconhecido: o pedido segue reservado e a cobrança pode ser gerada novamente.
            report($exception);
        }

        return $order->refresh();
    }

    /**
     * Gera novamente a cobrança de um pedido pendente cuja criação falhou de forma transitória.
     */
    public function retryPayment(Order $order): void
    {
        try {
            $this->payments->startPayment($order);
        } catch (PaymentGatewayException $exception) {
            throw new CheckoutException($exception->definitive
                ? 'O gateway recusou a criação do pagamento e a reserva foi liberada.'
                : 'O gateway de pagamento não respondeu. Tente novamente em instantes.');
        }
    }

    /**
     * Devolve ao carrinho os itens de um pedido cancelado antes de existir cobrança.
     */
    private function restoreCart(User $user, Order $order): void
    {
        DB::transaction(function () use ($user, $order): void {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            foreach ($order->items()->get() as $item) {
                CartItem::firstOrCreate(
                    ['cart_id' => $cart->id, 'listing_id' => $item->listing_id],
                    ['quantity' => $item->quantity, 'unit_price' => $item->unit_price]
                );
            }
        });
    }

    private function findByKey(User $user, string $checkoutKey): ?Order
    {
        return Order::query()->where('buyer_id', $user->id)->where('checkout_key', $checkoutKey)->first();
    }

    /**
     * O checkout revalida o preço: se o anúncio mudou, atualiza o carrinho e pede revisão.
     */
    private function syncChangedPrices(User $user): void
    {
        $cart = Cart::with('items.listing')->where('user_id', $user->id)->first();
        $changed = $cart?->items->filter(fn (CartItem $item): bool => $item->hasPriceChanged()) ?? collect();

        if ($changed->isEmpty()) {
            return;
        }

        foreach ($changed as $item) {
            $item->update(['unit_price' => $item->listing->price]);
        }

        $titles = $changed->map(fn (CartItem $item): string => '“'.$item->listing->title.'”')->implode(', ');

        throw new CheckoutException("O preço de {$titles} foi atualizado pelo vendedor. Revise o carrinho antes de continuar.");
    }

    private function reserve(User $user, string $checkoutKey, ?string $notes, array $itemInstructions): Order
    {
        $cart = Cart::query()->where('user_id', $user->id)->first();
        $cartItems = $cart === null ? collect() : CartItem::query()
            ->where('cart_id', $cart->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($cartItems->isEmpty()) {
            throw new CheckoutException('O carrinho está vazio.');
        }

        // Ordem crescente de id evita deadlock entre checkouts concorrentes (D04).
        $listings = Listing::query()
            ->with(['game', 'category', 'seller.sellerProfile'])
            ->whereIn('id', $cartItems->pluck('listing_id')->unique()->sort()->values())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        Reservation::query()
            ->whereIn('listing_id', $listings->keys())
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'released', 'released_reason' => 'expired']);

        $lines = $this->validateLines($user, $cartItems, $listings);
        $totalCents = $lines->sum(fn (array $line): int => $line['price_cents'] * $line['quantity']);
        $expiresAt = now()->addMinutes(Reservation::TTL_MINUTES);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)).'-'.now()->format('Ymd'),
            'checkout_key' => $checkoutKey,
            'buyer_id' => $user->id,
            'total_amount' => $this->toDecimal($totalCents),
            'currency' => 'BRL',
            'status' => 'pendente',
            'notes' => $notes,
            'reservation_expires_at' => $expiresAt,
        ]);

        foreach ($lines as $line) {
            /** @var Listing $listing */
            $listing = $line['listing'];
            $isService = $line['type'] === 'service';

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'listing_description' => $listing->description,
                'listing_type' => $line['type'],
                'game_id' => $listing->game_id,
                'game_name' => $listing->game?->name,
                'category_id' => $listing->category_id,
                'category_name' => $listing->category?->name,
                'seller_id' => $listing->seller_id,
                'unit_price' => $this->toDecimal($line['price_cents']),
                'currency' => 'BRL',
                'quantity' => $line['quantity'],
                'session_duration_minutes' => $isService ? $listing->session_duration_minutes : null,
                'delivery_instructions' => $itemInstructions[$line['cart_item_id']] ?? null,
                'delivery_conditions' => $listing->delivery_conditions,
                'scheduling_mode' => $isService ? 'by_chat' : 'not_applicable',
                'timezone' => $isService ? $listing->timezone : null,
                'delivery_status' => 'aguardando_pagamento',
            ]);

            Reservation::create([
                'listing_id' => $listing->id,
                'order_item_id' => $orderItem->id,
                'quantity' => $line['quantity'],
                'status' => 'active',
                'expires_at' => $expiresAt,
            ]);
        }

        CartItem::query()->whereIn('id', $cartItems->modelKeys())->delete();

        return $order;
    }

    /**
     * Revalida elegibilidade, quantidade, preço e saldo de todos os itens; qualquer falha reverte tudo (D12).
     *
     * @param  Collection<int, CartItem>  $cartItems
     * @param  Collection<int, Listing>  $listings
     * @return Collection<int, array{listing: Listing, cart_item_id: int, type: string, quantity: int, price_cents: int}>
     */
    private function validateLines(User $user, Collection $cartItems, Collection $listings): Collection
    {
        $requested = [];

        return $cartItems->map(function (CartItem $item) use ($user, $listings, &$requested): array {
            /** @var Listing|null $listing */
            $listing = $listings->get($item->listing_id);

            if ($listing === null || ! $listing->isAvailable()) {
                $title = $listing?->title ?? 'removido';

                throw new CheckoutException("O anúncio “{$title}” não está mais disponível.");
            }

            if ((int) $listing->seller_id === (int) $user->id) {
                throw new CheckoutException('Você não pode comprar seu próprio anúncio.');
            }

            $type = $listing->category->type;
            $quantity = $item->quantity;

            if (($type === 'cosmetic' && $quantity !== 1) || ($type === 'service' && $quantity < 1)) {
                throw new CheckoutException("Quantidade inválida para “{$listing->title}”.");
            }

            $priceCents = (int) round((float) $listing->price * 100);

            if ($priceCents !== (int) round((float) $item->unit_price * 100)) {
                throw new CheckoutException("O preço de “{$listing->title}” mudou. Revise o carrinho antes de continuar.");
            }

            if ($priceCents < self::MIN_UNIT_PRICE_CENTS) {
                throw new CheckoutException("O anúncio “{$listing->title}” tem valor abaixo do mínimo aceito (R$ 1,00).");
            }

            $requested[$listing->id] = ($requested[$listing->id] ?? 0) + $quantity;

            if ($requested[$listing->id] > $listing->availableQuantity()) {
                throw new CheckoutException("Quantidade indisponível para “{$listing->title}” no momento.");
            }

            return [
                'listing' => $listing,
                'cart_item_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'price_cents' => $priceCents,
            ];
        });
    }

    private function toDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
