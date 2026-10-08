<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('previous_report_status', 20)->nullable();
            $table->string('new_report_status', 20);
            $table->string('previous_listing_status', 20)->nullable();
            $table->string('new_listing_status', 20)->nullable();
            $table->text('resolution_notes');
            $table->timestamps();

            $table->index(['report_id', 'created_at']);
            $table->index(['moderator_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_audits');
    }
};
