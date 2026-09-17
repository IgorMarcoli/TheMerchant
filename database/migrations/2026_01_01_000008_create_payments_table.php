<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway', 50);
            $table->string('transaction_id', 100)->index();
            $table->string('status', 50);
            $table->decimal('amount', 10, 2);
            $table->string('idempotency_key', 128)->unique(); // Prevenção de duplicidade de webhook (RNF06/RN06)
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
