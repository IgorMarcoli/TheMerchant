<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $user = User::where('email', $this->email)->lockForUpdate()->first();
            if ($user?->isActive()) {
                Password::sendResetLink(['email' => $this->email, 'status' => 'active']);
            }
        });
    }
}
