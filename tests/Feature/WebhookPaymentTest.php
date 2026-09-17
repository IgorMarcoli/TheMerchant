<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Listing;
use App\Models\Game;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_dispatches_successfully(): void
    {
        $payload = [
            'action' => 'payment.created',
            'data' => [
                'id' => '123456789',
            ],
            'external_reference' => 'ORD-TEST-001',
        ];

        $response = $this->postJson(route('api.webhooks.payment'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'received',
        ]);
    }
}
