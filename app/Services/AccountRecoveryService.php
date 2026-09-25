<?php

namespace App\Services;

use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;

class AccountRecoveryService
{
    public function requestReset(string $email): void
    {
        // Dispatch for every valid address: account lookup happens outside the HTTP request.
        SendPasswordResetLink::dispatch($email);
    }

    public function reset(array $credentials): bool
    {
        // Keep missing/suspended accounts from returning faster than invalid tokens.
        return (new Timebox)->call(fn (): bool => $this->resetAccount($credentials), 300000);
    }

    private function resetAccount(array $credentials): bool
    {
        return DB::transaction(function () use ($credentials): bool {
            // Serialize resets for this account so concurrent requests cannot reuse a token.
            $account = User::where('email', $credentials['email'])->lockForUpdate()->first();
            if (! $account || ! $account->isActive()) {
                return false;
            }

            $status = Password::reset([...$credentials, 'status' => 'active'], function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                DB::afterCommit(fn () => event(new PasswordReset($user)));
            });

            return $status === Password::PASSWORD_RESET;
        });
    }
}
