<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Http\Requests\StoreListingReportRequest;
use App\Models\Listing;
use App\Models\Report;
use App\Services\CatalogService;
use App\Services\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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
            ->whereNotIn('status', ['rascunho', 'bloqueado'])
            ->whereHas('seller', fn ($seller) => $seller->where('status', 'active')
                ->whereHas('sellerProfile', fn ($profile) => $profile->where('status', 'approved')))
            ->where('slug', $slug)
            ->firstOrFail();

        $listing->increment('views_count');
        $galleryImages = $listing->images
            ->filter(fn ($image) => Storage::disk('public')->exists($image->image_path))
            ->sortBy(fn ($image) => [$image->is_primary ? 0 : 1, $image->display_order])
            ->values()
            ->map(fn ($image) => Storage::disk('public')->url($image->image_path));

        return view('listings.show', compact('listing', 'galleryImages'));
    }

    public function report(StoreListingReportRequest $request, Listing $listing): RedirectResponse
    {
        Report::create([
            'reporter_id' => $request->user()->id,
            'listing_id' => $listing->id,
            ...$request->validated(),
            'status' => 'aberta',
        ]);

        return back()->with('success', 'Denúncia enviada com sucesso para análise da equipe de moderação.');
    }
}
