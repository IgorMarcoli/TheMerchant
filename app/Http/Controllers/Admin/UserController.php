<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = User::with('sellerProfile');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('busca')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->busca}%")
                  ->orWhere('email', 'like', "%{$request->busca}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'role'   => ['required', 'in:buyer,seller,admin'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $usuario->update($validated);

        return back()->with('success', "Usuário {$usuario->name} atualizado com sucesso.");
    }
}
