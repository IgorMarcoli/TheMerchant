<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->unsignedBigInteger('buyer_last_read_message_id')->nullable();
            $table->unsignedBigInteger('seller_last_read_message_id')->nullable();
            $table->timestamps();

            $table->unique(['buyer_id', 'seller_id', 'listing_id'], 'conversations_participants_listing_unique');
            $table->index(['buyer_id', 'updated_at']);
            $table->index(['seller_id', 'updated_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('client_uuid', 64);
            $table->text('body');
            $table->timestamps();

            $table->unique(['conversation_id', 'sender_id', 'client_uuid'], 'messages_conversation_sender_client_uuid_unique');
            $table->index(['conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};

