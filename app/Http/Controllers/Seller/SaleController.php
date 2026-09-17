<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = OrderItem::with(['order.buyer', 'listing.primaryImage'])
            ->where('seller_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('seller.sales.index', compact('sales'));
    }

    public function markAsDelivered(Request $request, OrderItem $item): RedirectResponse
    {
        abort_unless($item->seller_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($item->order->isPaid(), 400, 'O pedido precisa estar pago antes da confirmação de entrega.');

        DB::transaction(function () use ($item) {
            $item->update([
                'delivery_status' => 'entregue',
                'delivered_at'    => now(),
            ]);

            // Se todos os itens do pedido foram entregues, marca pedido como concluído
            $order = $item->order;
            $allDelivered = $order->items()->where('delivery_status', '!=', 'entregue')->doesntExist();

            if ($allDelivered) {
                $order->update(['status' => 'concluido']);
            }

            // Incrementa contador de vendas do perfil do vendedor
            $profile = SellerProfile::where('user_id', $item->seller_id)->first();
            if ($profile) {
                $profile->increment('total_sales');
            }
        });

        return back()->with('success', 'Item marcado como entregue com sucesso!');
    }
}
