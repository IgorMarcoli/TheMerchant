<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'capacity',
        'session_duration_minutes',
        'delivery_conditions',
        'timezone',
        'archived_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'views_count' => 'integer',
        'capacity' => 'integer',
        'session_duration_minutes' => 'integer',
        'archived_at' => 'datetime',
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

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'listing_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('status', 'publicado')->where('capacity', '>', 0)
            ->whereHas('game', fn ($game) => $game->where('active', true))
            ->whereHas('category', fn ($category) => $category->where('is_active', true))
            ->whereHas('seller', fn ($seller) => $seller->where('status', 'active')
                ->whereHas('sellerProfile', fn ($profile) => $profile->where('status', 'approved')));
    }

    public function isAvailable(): bool
    {
        return $this->archived_at === null && $this->status === 'publicado' && $this->capacity > 0
            && ($this->game?->active ?? false) && ($this->category?->is_active ?? false)
            && ($this->seller?->isSeller() ?? false);
    }
}
