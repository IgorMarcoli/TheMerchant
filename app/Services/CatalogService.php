<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\SellerProfile;

class CatalogService
{
    public function search(array $filters): array
    {
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $query = Listing::with(['game', 'category', 'seller.sellerProfile', 'primaryImage'])->available();

        if (isset($filters['tipo'])) {
            $query->whereHas('category', fn ($category) => $category->where('type', $filters['tipo']));
        }
        if (isset($filters['busca'])) {
            $query->where('title', 'like', '%'.$filters['busca'].'%');
        }
        foreach (['jogo' => 'game_id', 'categoria' => 'category_id'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        foreach (['preco_min' => '>=', 'preco_max' => '<='] as $filter => $operator) {
            if (isset($filters[$filter])) {
                $query->where('price', $operator, $filters[$filter]);
            }
        }
        if (isset($filters['nota_min'])) {
            $query->whereHas('seller.sellerProfile', fn ($profile) => $profile
                ->where('total_reviews', '>', 0)->where('reputation_score', '>=', $filters['nota_min']));
        }

        match ($filters['ordem'] ?? 'recentes') {
            'menor_preco' => $query->orderBy('price'),
            'maior_preco' => $query->orderByDesc('price'),
            'reputacao' => $query->orderByDesc(SellerProfile::selectRaw('CASE WHEN total_reviews > 0 THEN reputation_score ELSE -1 END')
                ->whereColumn('user_id', 'listings.seller_id')->limit(1)),
            default => $query->latest(),
        };

        return [
            'listings' => $query->orderByDesc('listings.id')->paginate(12)->appends($filters),
            'games' => Game::where('active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ];
    }
}
