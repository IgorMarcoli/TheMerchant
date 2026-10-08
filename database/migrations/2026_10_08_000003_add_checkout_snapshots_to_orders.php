<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('reservation_expires_at')->nullable()->after('notes');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('listing_title')->nullable()->after('listing_id');
            $table->text('listing_description')->nullable()->after('listing_title');
            $table->text('delivery_instructions')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['listing_title', 'listing_description', 'delivery_instructions']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('reservation_expires_at');
        });
    }
};
