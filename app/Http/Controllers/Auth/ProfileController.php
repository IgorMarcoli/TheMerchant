<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    /**
     * Exibe o formulário de perfil e dados da conta.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('sellerProfile');

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Atualiza as informações cadastrais do usuário (nome, e-mail e biografia se vendedor).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'bio'   => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Se o usuário for vendedor e tiver perfil associado, atualiza a biografia
        if ($user->isSeller() && $user->sellerProfile) {
            $user->sellerProfile->update([
                'bio' => $validated['bio'] ?? $user->sellerProfile->bio,
            ]);
        }

        return redirect()->route('profile.edit')->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Atualiza a senha de acesso do usuário.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'A senha atual informada está incorreta.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.edit')->with('success', 'Senha alterada com sucesso!');
    }
}
