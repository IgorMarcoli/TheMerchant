<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ListingPublicController extends Controller
{
    public function home(): View
    {
        $dbStatus = [
            'connected'     => false,
            'driver'        => config('database.default'),
            'database'      => config('database.connections.' . config('database.default') . '.database', 'themerchant'),
            'host'          => config('database.connections.' . config('database.default') . '.host', '127.0.0.1'),
            'port'          => config('database.connections.' . config('database.default') . '.port', '3306'),
            'hasUsersTable' => false,
            'usersCount'    => 0,
            'tables'        => [],
            'error'         => null,
        ];

        try {
            DB::connection()->getPdo();
            $dbStatus['connected'] = true;
            $dbStatus['hasUsersTable'] = Schema::hasTable('users');
            if ($dbStatus['hasUsersTable']) {
                $dbStatus['usersCount'] = User::count();
            }
            $tablesRaw = DB::select('SHOW TABLES');
            $dbStatus['tables'] = array_map(function ($t) {
                return array_values((array)$t)[0];
            }, $tablesRaw);
        } catch (\Throwable $e) {
            $dbStatus['error'] = $e->getMessage();
        }

        return view('home', compact('dbStatus'));
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
