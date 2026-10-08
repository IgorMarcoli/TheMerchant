<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordSaleDeliveryRequest;
use App\Models\OrderItem;
use App\Services\SaleDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', OrderItem::class);
        $sales = OrderItem::with(['order.buyer', 'listing.primaryImage', 'listing.category', 'deliveries'])
            ->where('seller_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('seller.sales.index', compact('sales'));
    }

    public function markAsDelivered(RecordSaleDeliveryRequest $request, OrderItem $item, SaleDeliveryService $service): RedirectResponse
    {
        $result = $service->record(
            $request->user(),
            $item,
            $request->validated('session_number') !== null
                ? (int) $request->validated('session_number')
                : null,
        );

        $message = match ($result) {
            'session_recorded' => 'Sessão registrada. O item será concluído após todas as sessões contratadas.',
            'already_recorded' => 'Essa sessão já havia sido registrada; nenhuma contagem foi alterada.',
            'already_delivered' => 'Esse item já estava entregue; nenhuma contagem foi alterada.',
            default => 'Entrega concluída com sucesso!',
        };

        return back()->with('success', $message);
    }
}
