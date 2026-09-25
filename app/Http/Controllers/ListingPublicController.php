<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Models\Listing;
use App\Models\Report;
use App\Services\CatalogService;
use App\Services\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingPublicController extends Controller
{
    public function home(StorefrontService $storefront): View
    {
        return view('home', $storefront->home());
    }

    public function index(CatalogRequest $request, CatalogService $catalog): View
    {
        return view('listings.index', $catalog->search($request->validated()));
    }

    public function show(string $slug): View
    {
        $listing = Listing::with(['game', 'category', 'seller.sellerProfile', 'images'])
            ->available()
            ->where('slug', $slug)
            ->firstOrFail();

        $listing->increment('views_count');

        return view('listings.show', compact('listing'));
    }

    public function report(Request $request, Listing $listing): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'listing_id' => $listing->id,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'aberta',
        ]);

        return back()->with('success', 'Denúncia enviada com sucesso para análise da equipe de moderação.');
    }
}
