<?php

namespace App\Services;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SellerAccountService
{
    public function updateProfile(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            $emailChanged = $user->isDirty('email');
            if ($emailChanged) {
                DB::table('password_reset_tokens')->whereIn('email', [$user->getOriginal('email'), $user->email])->delete();
                $user->email_verified_at = null;
            }
            $user->save();
            if (array_key_exists('bio', $data)) {
                $user->sellerProfile()->update(['bio' => $data['bio']]);
            }
            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }
        });
    }

    public function apply(User $user, string $bio): SellerProfile
    {
        return DB::transaction(function () use ($user, $bio): SellerProfile {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            return $account->sellerProfile()->firstOrCreate([], ['bio' => $bio, 'status' => 'pending']);
        });
    }

    public function updatePermissions(User $actor, User $target, array $data): void
    {
        DB::transaction(function () use ($actor, $target, $data): void {
            $account = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($actor->id === $account->id && (! $data['is_admin'] || $data['status'] !== 'active')) {
                throw ValidationException::withMessages(['is_admin' => 'Você não pode remover seu próprio acesso administrativo.']);
            }
            $profile = $account->sellerProfile()->first();
            if (! empty($data['seller_status']) && ! $profile) {
                throw ValidationException::withMessages(['seller_status' => 'Esta conta ainda não solicitou um perfil de vendedor.']);
            }
            $account->is_admin = (bool) $data['is_admin'];
            $account->status = $data['status'];
            $account->save();
            if ($profile && ! empty($data['seller_status'])) {
                $profile->update(['status' => $data['seller_status']]);
            }

            if ($data['status'] === 'suspended' && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }
        });
    }
}
