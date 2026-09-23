<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false);
        });
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending')->index();
        });
        DB::statement("UPDATE users SET is_admin = true WHERE role = 'admin'");
        // Preserve existing sellers, including legacy sellers without a profile.
        DB::statement("INSERT INTO seller_profiles (user_id, status, created_at, updated_at)
            SELECT id, 'approved', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM users
            WHERE role = 'seller' AND NOT EXISTS
            (SELECT 1 FROM seller_profiles WHERE seller_profiles.user_id = users.id)");
        DB::statement("UPDATE seller_profiles SET status = 'approved' WHERE user_id IN
            (SELECT id FROM users WHERE role IN ('seller', 'admin'))");
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_index');
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['buyer', 'seller', 'admin'])->default('buyer')->index();
        });
        DB::statement("UPDATE users SET role = 'seller' WHERE id IN
            (SELECT user_id FROM seller_profiles WHERE status = 'approved')");
        DB::statement("UPDATE users SET role = 'admin' WHERE is_admin = true");
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropIndex('seller_profiles_status_index');
            $table->dropColumn('status');
        });
    }
};
