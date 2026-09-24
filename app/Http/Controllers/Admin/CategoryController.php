<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCategoryStoreRequest;
use App\Http\Requests\AdminCategoryUpdateRequest;
use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::with('game')->withCount('listings');

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->integer('game_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->value() === 'active');
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();
        $games = Game::orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'games'));
    }

    public function store(AdminCategoryStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Category::create([
            'game_id' => $validated['game_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function update(AdminCategoryUpdateRequest $request, Category $categoria): RedirectResponse
    {
        $validated = $request->validated();

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $categoria->update([
            'game_id' => $validated['game_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Categoria atualizada com sucesso!');
    }

    public function toggleStatus(Category $categoria): RedirectResponse
    {
        $this->authorize('update', $categoria);

        $categoria->is_active = ! $categoria->is_active;
        $categoria->save();

        $statusMsg = $categoria->is_active ? 'ativada' : 'inativada';

        return back()->with('success', "Categoria '{$categoria->name}' {$statusMsg} com sucesso!");
    }

    public function destroy(Category $categoria): RedirectResponse
    {
        $this->authorize('delete', $categoria);

        // Não permitir exclusão que quebre pedidos ou histórico de anúncios
        if ($categoria->listings()->exists()) {
            return back()->with('error', "Não é possível excluir a categoria '{$categoria->name}' porque existem anúncios vinculados a ela. Em vez disso, inative-a para impedir novos anúncios preservando o histórico.");
        }

        $categoria->delete();

        return back()->with('success', "Categoria '{$categoria->name}' excluída com sucesso.");
    }

    public function toggleGameStatus(Game $game): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $game->active = ! $game->active;
        $game->save();

        $statusMsg = $game->active ? 'ativado' : 'inativado';

        return back()->with('success', "Jogo '{$game->name}' {$statusMsg} com sucesso!");
    }
}
