<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Acessar Conta');
    }

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'gamer@example.com',
            'password' => Hash::make('secret12345'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'gamer@example.com',
            'password' => 'secret12345',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'gamer@example.com',
            'password' => Hash::make('secret12345'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'gamer@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Criar Nova Conta');
    }

    public function test_visitor_can_register_as_buyer(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Novo Comprador',
            'email' => 'comprador@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'comprador@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->sellerProfile);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nome Antigo',
            'email' => 'antigo@example.com',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Nome Novo',
            'email' => 'novo@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nome Novo',
            'email' => 'novo@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('antiga123'),
        ]);

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'antiga123',
            'password' => 'nova12345',
            'password_confirmation' => 'nova12345',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertTrue(Hash::check('nova12345', $user->fresh()->password));
    }

    public function test_authenticated_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('antiga123'),
        ]);

        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'senha_errada',
            'password' => 'nova12345',
            'password_confirmation' => 'nova12345',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('antiga123', $user->fresh()->password));
    }
}
