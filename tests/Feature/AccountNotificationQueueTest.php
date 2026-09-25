<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountNotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database', 'mail.default' => 'array']);
    }

    public function test_database_worker_delivers_verification_and_its_signed_link_works(): void
    {
        $messages = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$messages): void {
            $messages[] = $event->message;
        });
        $this->post(route('register'), [
            'name' => 'Fila', 'email' => 'queue@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));
        $this->assertDatabaseCount('jobs', 1);
        $this->assertCount(0, $messages);
        $url = null;
        Event::listen(NotificationSent::class, function (NotificationSent $event) use (&$url): void {
            if ($event->notification instanceof VerifyEmailNotification) {
                $url = $event->notification->toMail($event->notifiable)->actionUrl;
            }
        });
        $this->runWorker();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Confirme seu e-mail', $messages[0]->getSubject());
        $this->assertNotNull($url);
        $this->get($url)->assertRedirect(route('profile.edit'));
        $this->assertTrue(User::firstOrFail()->hasVerifiedEmail());
    }

    public function test_worker_sends_reset_token_asynchronously_and_it_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = null;
        Event::listen(NotificationSent::class, function (NotificationSent $event) use (&$token): void {
            if ($event->notification instanceof ResetPasswordNotification) {
                $token = $event->notification->token;
            }
        });
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertNull($token);
        $this->assertStringNotContainsString($user->email, DB::table('jobs')->value('payload'));
        $this->runWorker();
        $this->assertNotNull($token);
        $this->assertTrue(Hash::check($token, DB::table('password_reset_tokens')->value('token')));
        $data = [
            'email' => $user->email, 'token' => $token,
            'password' => 'replacement123', 'password_confirmation' => 'replacement123',
        ];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
    }

    public function test_worker_drops_obsolete_notifications_after_email_change_or_suspension(): void
    {
        $messages = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$messages): void {
            $messages[] = $event->message;
        });
        $user = User::factory()->create();
        $user->sendEmailVerificationNotification();
        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);
        $this->assertStringNotContainsString($token, DB::table('jobs')->orderByDesc('id')->value('payload'));
        $user->update(['email' => 'changed@example.test']);
        $this->runWorker();
        $this->assertCount(0, $messages);

        $user->sendEmailVerificationNotification();
        SendPasswordResetLink::dispatch($user->email);
        $user->update(['status' => 'suspended']);
        $this->runWorker();
        $this->assertCount(0, $messages);
    }

    public function test_transaction_rollback_does_not_enqueue_verification(): void
    {
        $user = User::factory()->create();
        DB::beginTransaction();
        $user->sendEmailVerificationNotification();
        $this->assertDatabaseCount('jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
    }

    private function runWorker(): void
    {
        Artisan::call('queue:work', [
            'connection' => 'database', '--stop-when-empty' => true,
            '--sleep' => 0, '--tries' => 1, '--max-jobs' => 10,
        ]);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
    }
}
