<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedSmallInteger('session_duration_minutes')->nullable();
            $table->text('delivery_conditions')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::table('listing_images', function (Blueprint $table): void {
            $table->string('disk', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', fn (Blueprint $table) => $table->dropColumn('disk'));
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn([
            'capacity', 'session_duration_minutes', 'delivery_conditions', 'timezone', 'archived_at',
        ]));
    }
};
