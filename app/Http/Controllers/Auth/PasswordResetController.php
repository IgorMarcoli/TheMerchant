<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AccountRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function send(ForgotPasswordRequest $request, AccountRecoveryService $service): RedirectResponse
    {
        $service->requestReset($request->validated('email'));

        return back()->with('success', 'Se houver uma conta habilitada para este e-mail, enviaremos as instruções de recuperação.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function update(ResetPasswordRequest $request, AccountRecoveryService $service): RedirectResponse
    {
        if (! $service->reset($request->validated())) {
            return back()->withErrors(['email' => 'Não foi possível redefinir a senha. Solicite um novo link e tente novamente.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Senha redefinida. Entre novamente com sua nova senha.');
    }
}
