<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerApplicationRequest;
use App\Services\SellerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function show(Request $request): View
    {
        return view('seller.application', ['profile' => $request->user()->sellerProfile]);
    }

    public function store(SellerApplicationRequest $request, SellerAccountService $service): RedirectResponse
    {
        $service->apply($request->user(), $request->validated('bio'));

        return redirect()->route('seller.application')->with('success', 'Perfil registrado. Consulte abaixo a situação da sua solicitação.');
    }
}
