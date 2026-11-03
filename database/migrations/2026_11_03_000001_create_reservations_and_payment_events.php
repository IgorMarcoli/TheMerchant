<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('checkout_key', 64)->nullable()->after('order_number');
            $table->char('currency', 3)->default('BRL')->after('total_amount');
            $table->unique(['buyer_id', 'checkout_key']);
        });

        // Snapshot imutável da oferta no ato do checkout (contrato #29, seção 3).
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('listing_type', 16)->nullable()->after('listing_description');
            $table->unsignedBigInteger('game_id')->nullable()->after('listing_type');
            $table->string('game_name')->nullable()->after('game_id');
            $table->unsignedBigInteger('category_id')->nullable()->after('game_name');
            $table->string('category_name')->nullable()->after('category_id');
            $table->char('currency', 3)->default('BRL')->after('unit_price');
            $table->unsignedSmallInteger('session_duration_minutes')->nullable()->after('quantity');
            $table->text('delivery_conditions')->nullable()->after('delivery_instructions');
            $table->string('scheduling_mode', 20)->nullable()->after('delivery_conditions');
            $table->string('timezone', 64)->nullable()->after('scheduling_mode');
            $table->timestamp('scheduled_at')->nullable()->after('timezone');
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 16)->default('active'); // active, confirmed, released
            $table->timestamp('expires_at');
            $table->string('released_reason', 32)->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'status', 'expires_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('method', 16)->nullable()->after('gateway');
            $table->boolean('dev_mode')->default(false)->after('status');
            $table->text('br_code')->nullable()->after('payload');
            $table->longText('br_code_base64')->nullable()->after('br_code');
            $table->timestamp('expires_at')->nullable()->after('br_code_base64');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
            $table->string('refund_status', 16)->nullable()->index()->after('paid_at');
            $table->string('refund_id', 100)->nullable()->after('refund_status');
            $table->unsignedSmallInteger('refund_attempts')->default(0)->after('refund_id');
            $table->string('last_error')->nullable()->after('refund_attempts');
        });

        // Identidade de cada evento recebido, separada da idempotência de criação da cobrança.
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 32);
            $table->string('event_id', 100)->unique();
            $table->string('event_type', 64);
            $table->string('charge_id', 100)->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('result', 32)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['refund_status']);
            $table->dropColumn([
                'method', 'dev_mode', 'br_code', 'br_code_base64', 'expires_at', 'paid_at',
                'refund_status', 'refund_id', 'refund_attempts', 'last_error',
            ]);
        });

        Schema::dropIfExists('reservations');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'listing_type', 'game_id', 'game_name', 'category_id', 'category_name', 'currency',
                'session_duration_minutes', 'delivery_conditions', 'scheduling_mode', 'timezone', 'scheduled_at',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['buyer_id', 'checkout_key']);
            $table->dropColumn(['checkout_key', 'currency']);
        });
    }
};
