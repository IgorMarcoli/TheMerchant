<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDelivery;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaleDeliveryService
{
    public function record(User $seller, OrderItem $item, ?int $sessionNumber = null): string
    {
        return DB::transaction(function () use ($seller, $item, $sessionNumber): string {
            $order = Order::query()->lockForUpdate()->findOrFail($item->order_id);
            $lockedItem = OrderItem::query()
                ->with('listing.category')
                ->lockForUpdate()
                ->findOrFail($item->id);

            Gate::forUser($seller)->authorize('deliver', $lockedItem);

            if (! $order->isPaid()) {
                throw ValidationException::withMessages([
                    'delivery' => 'O pedido precisa estar pago antes de confirmar uma entrega.',
                ]);
            }

            if ($lockedItem->delivery_status === 'entregue') {
                return 'already_delivered';
            }

            $isCoaching = $lockedItem->listing?->category?->type === 'service';
            $deliveryNumber = $isCoaching ? $sessionNumber : 1;

            if ($isCoaching && $deliveryNumber === null) {
                throw ValidationException::withMessages([
                    'session_number' => 'Informe a sessão de coaching concluída.',
                ]);
            }

            $existingDelivery = $lockedItem->deliveries()
                ->where('delivery_number', $deliveryNumber)
                ->exists();

            if ($existingDelivery) {
                return 'already_recorded';
            }

            $completedDeliveries = $lockedItem->deliveries()->count();
            $expectedDeliveryNumber = $completedDeliveries + 1;
            $maximumDeliveries = $isCoaching ? $lockedItem->quantity : 1;

            if ($deliveryNumber !== $expectedDeliveryNumber || $deliveryNumber > $maximumDeliveries) {
                throw ValidationException::withMessages([
                    'session_number' => 'Registre as sessões em ordem, sem ultrapassar a quantidade contratada.',
                ]);
            }

            $deliveredAt = now();
            OrderItemDelivery::create([
                'order_item_id' => $lockedItem->id,
                'delivery_number' => $deliveryNumber,
                'recorded_by' => $seller->id,
                'delivered_at' => $deliveredAt,
            ]);

            $isComplete = $deliveryNumber === $maximumDeliveries;

            if (! $isComplete) {
                return 'session_recorded';
            }

            $lockedItem->update([
                'delivery_status' => 'entregue',
                'delivered_at' => $deliveredAt,
            ]);

            if (! $order->items()->where('delivery_status', '!=', 'entregue')->exists()) {
                $order->update(['status' => 'concluido']);
            }

            $profile = SellerProfile::query()
                ->where('user_id', $lockedItem->seller_id)
                ->lockForUpdate()
                ->first();

            $profile?->increment('total_sales');

            return 'delivered';
        }, 5);
    }
}
