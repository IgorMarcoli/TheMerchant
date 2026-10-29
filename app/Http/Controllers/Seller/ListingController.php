<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingStoreRequest;
use App\Http\Requests\ListingUpdateRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request, ListingService $listings): View
    {
        $this->authorize('viewAny', Listing::class);

        return view('seller.listings.index', ['listings' => $listings->forSeller($request->user())]);
    }

    public function create(ListingService $listings): View
    {
        $this->authorize('create', Listing::class);

        return view('seller.listings.create', $listings->formData());
    }

    public function store(ListingStoreRequest $request, ListingService $listings): RedirectResponse
    {
        $listings->create($request->user(), $request->validated());

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio salvo com sucesso!');
    }

    public function edit(Listing $anuncio, ListingService $listings): View
    {
        $this->authorize('update', $anuncio);
        $anuncio->load('images');

        return view('seller.listings.edit', $listings->formData($anuncio));
    }

    public function update(ListingUpdateRequest $request, Listing $anuncio, ListingService $listings): RedirectResponse
    {
        $listings->update($request->user(), $anuncio, $request->validated());

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio atualizado com sucesso!');
    }

    public function toggleStatus(Request $request, Listing $listing, ListingService $listings): RedirectResponse
    {
        $this->authorize('changeStatus', $listing);
        $status = $listings->toggleStatus($request->user(), $listing);

        return back()->with('success', 'Status do anúncio alterado para: '.$status);
    }

    public function destroy(Request $request, Listing $anuncio, ListingService $listings): RedirectResponse
    {
        $this->authorize('delete', $anuncio);
        $listings->archive($request->user(), $anuncio);

        return redirect()->route('seller.anuncios.index')->with('success', 'Anúncio arquivado. Pedidos e conversas foram preservados.');
    }
}
