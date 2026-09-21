<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Listing;

class StorefrontService
{
    public function home(): array
    {
        return [
            'games' => Game::where('active', true)
                ->withCount(['listings' => fn ($query) => $query->where('status', 'publicado')])
                ->orderByDesc('listings_count')->orderBy('name')->limit(6)->get(),
            'listings' => Listing::with(['game', 'category', 'primaryImage'])
                ->where('status', 'publicado')->latest()->orderByDesc('id')->limit(4)->get(),
        ];
    }
}
