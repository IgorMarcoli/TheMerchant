<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'seller_id',
        'game_id',
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'status', // 'rascunho', 'publicado', 'pausado', 'vendido', 'bloqueado'
        'views_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'views_count' => 'integer',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('display_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'publicado')
            ->whereHas('seller', fn ($seller) => $seller->where('status', 'active')
                ->whereHas('sellerProfile', fn ($profile) => $profile->where('status', 'approved')));
    }

    public function isAvailable(): bool
    {
        return $this->status === 'publicado' && ($this->seller?->isSeller() ?? false);
    }
}
