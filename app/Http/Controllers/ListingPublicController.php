<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ListingPublicController extends Controller
{
    public function home(): View
    {
        $featuredGames = Game::where('active', true)->withCount('listings')->take(6)->get();
        $recentListings = Listing::with(['game', 'category', 'seller', 'primaryImage'])
            ->where('status', 'publicado')
            ->latest()
            ->take(8)
            ->get();

        return view('home', compact('featuredGames', 'recentListings'));
    }

    public function index(Request $request): View
    {
        $query = Listing::with(['game', 'category', 'seller', 'primaryImage'])
            ->where('status', 'publicado');

        if ($request->filled('busca')) {
            $query->where('title', 'like', '%' . $request->busca . '%');
        }

        if ($request->filled('jogo')) {
            $query->where('game_id', $request->jogo);
        }

        if ($request->filled('categoria')) {
            $query->where('category_id', $request->categoria);
        }

        if ($request->filled('preco_min')) {
            $query->where('price', '>=', $request->preco_min);
        }

        if ($request->filled('preco_max')) {
            $query->where('price', '<=', $request->preco_max);
        }

        // Ordenação
        match ($request->get('ordem')) {
            'menor_preco' => $query->orderBy('price', 'asc'),
            'maior_preco' => $query->orderBy('price', 'desc'),
            default       => $query->latest(),
        };

        $listings = $query->paginate(12)->withQueryString();
        $games = Game::where('active', true)->get();
        $categories = Category::all();

        return view('listings.index', compact('listings', 'games', 'categories'));
    }

    public function show(string $slug): View
    {
        $listing = Listing::with(['game', 'category', 'seller.sellerProfile', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        $listing->increment('views_count');

        return view('listings.show', compact('listing'));
    }

    public function report(Request $request, Listing $listing): RedirectResponse
    {
        $request->validate([
            'reason'  => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'listing_id'  => $listing->id,
            'reason'      => $request->reason,
            'details'     => $request->details,
            'status'      => 'aberta',
        ]);

        return back()->with('success', 'Denúncia enviada com sucesso para análise da equipe de moderação.');
    }
}
