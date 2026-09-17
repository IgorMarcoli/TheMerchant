<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $categories = Category::with('game')->withCount('listings')->paginate(15);
        $games = Game::all();

        return view('admin.categories.index', compact('categories', 'games'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'game_id' => ['nullable', 'exists:games,id'],
            'name'    => ['required', 'string', 'max:100'],
            'type'    => ['required', 'in:cosmetic,service'],
        ]);

        Category::create([
            'game_id' => $validated['game_id'],
            'name'    => $validated['name'],
            'slug'    => Str::slug($validated['name']),
            'type'    => $validated['type'],
        ]);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function destroy(Category $categoria): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $categoria->delete();
        return back()->with('success', 'Categoria removida.');
    }
}
