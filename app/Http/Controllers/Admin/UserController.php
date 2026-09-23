<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserUpdateRequest;
use App\Models\User;
use App\Services\SellerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('sellerProfile');

        if ($request->query('access') === 'admin') {
            $query->where('is_admin', true);
        } elseif (in_array($request->query('access'), ['pending', 'approved', 'suspended'], true)) {
            $query->whereHas('sellerProfile', fn ($profile) => $profile->where('status', $request->query('access')));
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

    public function update(AdminUserUpdateRequest $request, User $usuario, SellerAccountService $service): RedirectResponse
    {
        $service->updatePermissions($request->user(), $usuario, $request->validated());

        return back()->with('success', 'Permissões atualizadas.');
    }
}
