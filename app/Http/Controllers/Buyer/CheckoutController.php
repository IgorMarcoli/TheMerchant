<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutException;
use App\Services\CheckoutService;
use App\Services\PaymentGatewayService;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected PaymentGatewayService $paymentService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $cart = Cart::with(['items.listing.primaryImage'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Seu carrinho está vazio para checkout.');
        }

        return view('checkout.index', compact('cart'));
    }

    public function process(CheckoutRequest $request): RedirectResponse
    {
        try {
            $order = $this->checkoutService->checkout(
                $request->user(),
                $request->validated('checkout_key'),
                $request->validated('notes'),
                $request->validated('item_instructions', [])
            );
        } catch (CheckoutException $exception) {
            return redirect()->route('cart.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['payment', 'items.listing']);

        return view('checkout.success', compact('order'));
    }

    public function cancel(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['payment', 'items.listing']);

        return view('checkout.cancel', compact('order'));
    }

    public function retryPayment(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        try {
            $this->checkoutService->retryPayment($order);
        } catch (CheckoutException $exception) {
            return redirect()->route('checkout.success', $order)->with('error', $exception->getMessage());
        }

        return redirect()->route('checkout.success', $order)->with('success', 'PIX gerado. Pague antes do fim da reserva.');
    }

    /**
     * Consulta o provedor; o retorno do navegador nunca confirma pagamento por si só.
     */
    public function refresh(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);
        $payment = $order->payment()->first();

        if ($payment === null) {
            return redirect()->route('checkout.success', $order)->with('warning', 'Ainda não há cobrança gerada para este pedido.');
        }

        try {
            $this->paymentService->syncPayment($payment);
        } catch (PaymentGatewayException) {
            return redirect()->route('checkout.success', $order)->with('error', 'Não foi possível consultar o gateway agora. Tente novamente em instantes.');
        }

        return redirect()->route('checkout.success', $order)->with('info', 'Status do pagamento atualizado com o gateway.');
    }

    public function simulate(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);
        $payment = $order->payment()->first();
        abort_if($payment === null || ! config('services.abacatepay.dev_mode'), 404);

        try {
            $this->paymentService->simulatePayment($payment);
        } catch (CheckoutException|PaymentGatewayException $exception) {
            return redirect()->route('checkout.success', $order)->with('error', $exception->getMessage());
        }

        return redirect()->route('checkout.success', $order)->with('info', 'Pagamento simulado no modo de desenvolvimento da AbacatePay.');
    }
}
