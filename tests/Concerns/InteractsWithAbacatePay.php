<?php

namespace Tests\Concerns;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Simula a API v2 da AbacatePay com estado por cobrança, para que os testes
 * exercitem a consulta autoritativa ao provedor e não confiem no corpo do webhook.
 */
trait InteractsWithAbacatePay
{
    protected const WEBHOOK_SECRET = 'segredo-webhook-teste';

    /** @var array<string, array{amount: int, status: string, externalId: string, refunded: bool}> */
    protected array $charges = [];

    /** Resposta forçada para a próxima criação de cobrança: 'definitive', 'transient' ou null. */
    protected ?string $nextCreateFailure = null;

    /** Quando verdadeiro, o estorno responde com indisponibilidade transitória (503). */
    protected bool $refundUnavailable = false;

    protected User $seller;

    protected Game $game;

    protected Category $cosmeticCategory;

    protected Category $serviceCategory;

    protected function setUpAbacatePay(): void
    {
        config([
            'services.abacatepay.api_key' => 'abc_dev_chave_de_teste',
            'services.abacatepay.base_url' => 'https://api.abacatepay.com/v2',
            'services.abacatepay.webhook_secret' => self::WEBHOOK_SECRET,
            'services.abacatepay.dev_mode' => false,
        ]);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->handleGatewayRequest($request));
    }

    protected function setUpMarketplace(): void
    {
        $this->seller = User::factory()->seller()->create(['name' => 'Vendedor Pro']);
        $this->game = Game::create(['name' => 'CS2', 'slug' => 'cs2', 'active' => true]);
        $this->cosmeticCategory = Category::create([
            'game_id' => $this->game->id, 'name' => 'Skins', 'slug' => 'skins', 'type' => 'cosmetic', 'is_active' => true,
        ]);
        $this->serviceCategory = Category::create([
            'game_id' => $this->game->id, 'name' => 'Coaching', 'slug' => 'coaching', 'type' => 'service', 'is_active' => true,
        ]);
    }

    protected function cosmetic(string $title = 'AK-47 Redline', float $price = 80.00): Listing
    {
        return Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->cosmeticCategory->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => 'Cosmético de teste.',
            'price' => $price,
            'status' => 'publicado',
            'capacity' => 1,
            'delivery_conditions' => 'Envio por troca na Steam.',
        ]);
    }

    protected function service(string $title = 'Coaching de mira', float $price = 50.00, int $capacity = 5, int $duration = 60): Listing
    {
        return Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->serviceCategory->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => 'Serviço de teste.',
            'price' => $price,
            'status' => 'publicado',
            'capacity' => $capacity,
            'session_duration_minutes' => $duration,
            'delivery_conditions' => 'Sessões combinadas pelo chat.',
            'timezone' => 'America/Sao_Paulo',
        ]);
    }

    protected function addToCart(User $buyer, Listing $listing, int $quantity = 1): void
    {
        $this->actingAs($buyer)->post(route('cart.add', $listing));

        if ($quantity !== 1) {
            // Grava direto para também cobrir quantidades que o carrinho recusaria.
            CartItem::query()->where('listing_id', $listing->id)
                ->whereHas('cart', fn ($cart) => $cart->where('user_id', $buyer->id))
                ->update(['quantity' => $quantity]);
        }
    }

    protected function checkout(User $buyer, ?string $key = null): TestResponse
    {
        return $this->actingAs($buyer)->post(route('checkout.process'), [
            'terms_agreed' => '1',
            'checkout_key' => $key ?? (string) Str::uuid(),
        ]);
    }

    protected function lastOrderOf(User $buyer): ?Order
    {
        return Order::query()->where('buyer_id', $buyer->id)->latest('id')->first();
    }

    protected function setChargeStatus(string $chargeId, string $status): void
    {
        $this->charges[$chargeId]['status'] = $status;
    }

    protected function postWebhook(array $payload, ?string $secret = self::WEBHOOK_SECRET, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature ??= base64_encode(hash_hmac('sha256', $body, (string) config('services.abacatepay.webhook_public_key'), true));
        $uri = '/api/webhooks/payment'.($secret === null ? '' : '?webhookSecret='.urlencode($secret));

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $body);
    }

    protected function completedEvent(string $chargeId, ?string $eventId = null, string $status = 'PAID'): array
    {
        return [
            'id' => $eventId ?? 'log_'.Str::random(12),
            'event' => 'transparent.completed',
            'apiVersion' => 2,
            'devMode' => true,
            'data' => [
                'transparent' => [
                    'id' => $chargeId,
                    'externalId' => $this->charges[$chargeId]['externalId'] ?? null,
                    'amount' => $this->charges[$chargeId]['amount'] ?? null,
                    'status' => $status,
                ],
                'customer' => ['name' => 'Maria Santos', 'taxId' => '529.***.247-**', 'email' => 'maria@exemplo.com'],
                'payerInformation' => ['method' => 'PIX', 'PIX' => ['name' => 'Maria Santos', 'taxId' => '529.***.247-**']],
            ],
        ];
    }

    protected function gatewayRequests(string $path): int
    {
        return count(Http::recorded(fn (Request $request): bool => str_contains($request->url(), $path)));
    }

    private function handleGatewayRequest(Request $request)
    {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        if (str_ends_with($path, '/transparents/create')) {
            if ($this->nextCreateFailure !== null) {
                $failure = $this->nextCreateFailure;
                $this->nextCreateFailure = null;

                return $failure === 'definitive'
                    ? Http::response(['data' => null, 'success' => false, 'error' => 'Valor inválido.'], 400)
                    : Http::response(['error' => 'Internal error'], 503);
            }

            $data = $request->data()['data'];
            $id = 'pix_char_'.Str::random(16);
            $this->charges[$id] = [
                'amount' => $data['amount'],
                'status' => 'PENDING',
                'externalId' => $data['externalId'],
                'refunded' => false,
            ];

            return Http::response(['data' => [
                'id' => $id,
                'amount' => $data['amount'],
                'status' => 'PENDING',
                'devMode' => true,
                'brCode' => '00020101021226950014br.gov.bcb.pix'.$id,
                'brCodeBase64' => 'data:image/png;base64,iVBORw0KGgo=',
                'platformFee' => 80,
                'expiresAt' => now()->addSeconds($data['expiresIn'])->toIso8601String(),
            ], 'success' => true, 'error' => null]);
        }

        $id = $query['id'] ?? ($request->data()['id'] ?? null);

        if (! isset($this->charges[$id])) {
            return Http::response(['error' => 'Billing not found'], 404);
        }

        if (str_ends_with($path, '/transparents/check')) {
            return Http::response(['data' => [
                'id' => $id, 'status' => $this->charges[$id]['status'], 'expiresAt' => now()->addMinutes(10)->toIso8601String(),
            ], 'success' => true, 'error' => null]);
        }

        if (str_ends_with($path, '/transparents/simulate-payment')) {
            $this->charges[$id]['status'] = 'PAID';

            return Http::response(['data' => ['id' => $id, 'status' => 'PAID'], 'success' => true, 'error' => null]);
        }

        if (str_ends_with($path, '/transparents/refund')) {
            if ($this->refundUnavailable) {
                return Http::response(['error' => 'Instável'], 503);
            }

            if ($this->charges[$id]['refunded']) {
                return Http::response(['data' => null, 'success' => false, 'error' => 'Esta cobrança já foi reembolsada.'], 400);
            }

            $this->charges[$id]['refunded'] = true;
            $this->charges[$id]['status'] = 'REFUNDED';

            return Http::response(['data' => [
                'id' => 'tran_'.Str::random(10), 'status' => 'COMPLETE', 'amount' => $this->charges[$id]['amount'], 'originalId' => $id,
            ], 'success' => true, 'error' => null]);
        }

        return Http::response(['error' => 'Rota não simulada'], 404);
    }
}
