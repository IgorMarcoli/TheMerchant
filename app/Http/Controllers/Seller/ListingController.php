<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingStoreRequest;
use App\Models\Game;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::with(['game', 'category', 'primaryImage'])
            ->where('seller_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('seller.listings.index', compact('listings'));
    }

    public function create(): View
    {
        $games = Game::where('active', true)->get();
        $categories = Category::all();

        return view('seller.listings.create', compact('games', 'categories'));
    }

    public function store(ListingStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $slug = Str::slug($data['title']) . '-' . Str::lower(Str::random(6));

            $listing = Listing::create([
                'seller_id'   => $request->user()->id,
                'game_id'     => $data['game_id'],
                'category_id' => $data['category_id'],
                'title'       => $data['title'],
                'slug'        => $slug,
                'description' => $data['description'],
                'price'       => $data['price'],
                'status'      => $data['status'] ?? 'publicado',
            ]);

            // Upload de imagens
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store("listings/{$listing->id}", 'public');

                    ListingImage::create([
                        'listing_id'    => $listing->id,
                        'image_path'    => $path,
                        'is_primary'    => $index === 0,
                        'display_order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio publicado com sucesso!');
    }

    public function edit(Listing $anuncio): View
    {
        abort_unless(auth()->id() === $anuncio->seller_id || auth()->user()->isAdmin(), 403);

        $games = Game::where('active', true)->get();
        $categories = Category::all();

        return view('seller.listings.edit', ['listing' => $anuncio, 'games' => $games, 'categories' => $categories]);
    }

    public function update(Request $request, Listing $anuncio): RedirectResponse
    {
        abort_unless(auth()->id() === $anuncio->seller_id || auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'game_id'     => ['required', 'exists:games,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'title'       => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'price'       => ['required', 'numeric', 'min:1.00'],
            'status'      => ['required', 'in:rascunho,publicado,pausado'],
        ]);

        $anuncio->update($validated);

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio atualizado com sucesso!');
    }

    public function toggleStatus(Listing $listing): RedirectResponse
    {
        abort_unless(auth()->id() === $listing->seller_id || auth()->user()->isAdmin(), 403);

        $newStatus = $listing->status === 'publicado' ? 'pausado' : 'publicado';
        $listing->update(['status' => $newStatus]);

        return back()->with('success', "Status do anúncio alterado para: {$newStatus}");
    }

    public function destroy(Listing $anuncio): RedirectResponse
    {
        abort_unless(auth()->id() === $anuncio->seller_id || auth()->user()->isAdmin(), 403);

        $anuncio->delete();

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio removido.');
    }
}
