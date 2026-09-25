<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_and_keeps_email_unverified(): void
    {
        Notification::fake();
        $this->post(route('register'), [
            'name' => 'Nova conta', 'email' => 'new@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $user = User::firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Confirmar e-mail');
    }

    public function test_signed_link_confirms_only_the_logged_in_recipient(): void
    {
        $user = User::factory()->create();
        $url = $this->verificationUrl($user);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->actingAs($user)->get($url)->assertRedirect(route('profile.edit'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $verifiedAt = $user->fresh()->email_verified_at;
        $this->get($url)->assertRedirect(route('profile.edit'));
        $this->assertEquals($verifiedAt, $user->fresh()->email_verified_at);
    }

    public function test_tampered_expired_and_wrong_email_links_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get($this->verificationUrl($user).'tampered')->assertForbidden();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
        $this->get($expired)->assertForbidden();
        $wrongEmail = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1('other@example.test'),
        ]);
        $this->get($wrongEmail)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_limited_and_verified_accounts_receive_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
        $this->post(route('verification.send'))->assertRedirect();
        $this->post(route('verification.send'))->assertStatus(429);
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 1);
        $this->travel(61)->seconds();
        $this->post(route('verification.send'))->assertRedirect();
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
        $user->markEmailAsVerified();
        $this->travel(61)->seconds();
        $this->post(route('verification.send'))->assertRedirect();
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
    }

    public function test_email_change_invalidates_verification_old_links_and_reset_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $oldEmail = $user->email;
        $oldLink = $this->verificationUrl($user);
        Password::createToken($user);
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name, 'email' => 'changed@example.test',
        ])->assertRedirect(route('profile.edit'));
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
        $this->get($oldLink)->assertForbidden();
        Notification::assertSentTo($user, VerifyEmailNotification::class, fn ($notification) => $notification->email === 'changed@example.test');
        $this->get($this->verificationUrl($user->fresh()))->assertRedirect(route('profile.edit'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unchanged_email_preserves_verification_without_new_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Outro nome', 'email' => $user->email])->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_suspended_user_cannot_verify_or_request_resend(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'suspended']);
        $this->actingAs($user)->get($this->verificationUrl($user))->assertForbidden();
        $this->post(route('verification.send'))->assertForbidden();
        $this->get(route('verification.notice'))->assertForbidden();
        Notification::assertNothingSent();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_recovery_forms_render_and_login_links_to_recovery(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Esqueci minha senha');
        $this->get(route('password.request'))->assertOk()->assertSee('Enviar link de recuperação');
        $this->get(route('password.reset', ['token' => 'example', 'email' => 'user@example.test']))
            ->assertOk()->assertSee('Salvar nova senha')->assertSee('user@example.test');
    }

    public function test_reset_request_has_same_response_for_existing_missing_and_suspended_accounts(): void
    {
        Queue::fake();
        $active = User::factory()->create();
        $suspended = User::factory()->create(['status' => 'suspended']);
        $messages = [];
        foreach ([$active->email, $suspended->email, 'missing@example.test'] as $email) {
            $this->from(route('password.request'))->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.request'))->assertSessionHasNoErrors();
            $messages[] = session('success');
            Queue::assertPushed(SendPasswordResetLink::class, fn ($job) => $job->email === $email);
        }
        $this->assertCount(1, array_unique($messages));
        Queue::assertPushed(SendPasswordResetLink::class, 3);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_worker_creates_hashed_token_only_for_active_account_and_throttles_resend(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        (new SendPasswordResetLink($user->email))->handle();
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user): bool {
            $hash = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

            return $hash !== $notification->token && Hash::check($notification->token, $hash);
        });
        (new SendPasswordResetLink($user->email))->handle();
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        $suspended = User::factory()->create(['status' => 'suspended']);
        (new SendPasswordResetLink($suspended->email))->handle();
        (new SendPasswordResetLink('missing@example.test'))->handle();
        Notification::assertNotSentTo($suspended, ResetPasswordNotification::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_reset_consumes_token_rotates_remember_token_and_revokes_sessions(): void
    {
        $user = User::factory()->create();
        $oldRememberToken = $user->remember_token;
        $token = Password::createToken($user);
        DB::table('sessions')->insert([
            'id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);
        $data = $this->resetData($user, $token);
        $this->post(route('password.update'), $data)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertNotEquals($oldRememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'new-password123'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_and_expired_reset_tokens_are_rejected_without_changing_password(): void
    {
        $user = User::factory()->create();
        $original = $user->password;
        $token = Password::createToken($user);
        $this->post(route('password.update'), $this->resetData($user, 'wrong-token'))->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $this->resetData($user, $token))->assertSessionHasErrors('email');
        $this->assertSame($original, $user->fresh()->password);
    }

    public function test_reset_for_suspended_or_missing_account_returns_same_generic_error(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $user->update(['status' => 'suspended']);
        $this->post(route('password.update'), $this->resetData($user, $token))->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $data = $this->resetData($user, $token);
        $data['email'] = 'missing@example.test';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->assertSame($error, session('errors')->first('email'));
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_requires_valid_email_strong_enough_password_and_confirmation(): void
    {
        Queue::fake();
        $this->post(route('password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        Queue::assertNothingPushed();
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $data = $this->resetData($user, $token);
        $data['password'] = 'short';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('password');
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->assertNull(session('_old_input.password'));
        $this->assertNull(session('_old_input.token'));
    }

    public function test_recovery_attempts_are_limited_for_both_send_and_reset(): void
    {
        Queue::fake();
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertStatus(429);
        $this->post(route('password.update'), ['email' => 'missing@example.test'])->assertStatus(429);
        Queue::assertPushed(SendPasswordResetLink::class, 3);
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
    }

    private function resetData(User $user, string $token): array
    {
        return [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password123', 'password_confirmation' => 'new-password123',
        ];
    }
}
