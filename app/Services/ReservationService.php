<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Cancela pedidos pendentes cujo prazo de reserva venceu e libera o saldo reservado.
     * Idempotente: repetir não altera capacidade nem pedidos já tratados (D05).
     */
    public function expireDueOrders(): int
    {
        $expired = 0;

        Order::query()
            ->where('status', 'pendente')
            ->where('reservation_expires_at', '<=', now())
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $orderId) use (&$expired): void {
                if ($this->cancelPendingOrder($orderId, 'expired', 'expired')) {
                    $expired++;
                }
            });

        return $expired;
    }

    /**
     * Cancela um pedido ainda pendente, liberando suas reservas sem alterar a capacidade.
     */
    public function cancelPendingOrder(int $orderId, string $reason, ?string $paymentStatus = null): bool
    {
        return DB::transaction(function () use ($orderId, $reason, $paymentStatus): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pendente') {
                return false;
            }

            $this->releaseActive($this->lockReservations($order), $reason);
            $order->update(['status' => 'cancelado']);

            if ($paymentStatus !== null) {
                Payment::query()->where('order_id', $order->id)->where('status', 'pending')
                    ->update(['status' => $paymentStatus]);
            }

            return true;
        });
    }

    /**
     * Bloqueia anúncios (id crescente) e depois as reservas do pedido, na mesma ordem do checkout.
     *
     * @return Collection<int, Reservation>
     */
    public function lockReservations(Order $order): Collection
    {
        $itemIds = $order->items()->pluck('id');
        $listingIds = $order->items()->pluck('listing_id')->unique()->sort()->values();

        Listing::query()->whereIn('id', $listingIds)->orderBy('id')->lockForUpdate()->get();

        return Reservation::query()->whereIn('order_item_id', $itemIds)->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     */
    public function releaseActive(Collection $reservations, string $reason): void
    {
        foreach ($reservations as $reservation) {
            // Reserva confirmed nunca volta a liberar saldo (D09).
            if ($reservation->status === 'active') {
                $reservation->update(['status' => 'released', 'released_reason' => $reason]);
            }
        }
    }
}
