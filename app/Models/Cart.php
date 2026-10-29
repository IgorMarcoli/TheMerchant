<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function total(): float
    {
        $totalCents = $this->items->sum(static function (CartItem $item): int {
            return $item->subtotalCents();
        });

        return $totalCents / 100;
    }

    public function hasUnavailableItems(): bool
    {
        return $this->items->contains(static fn (CartItem $item): bool => ! $item->isAvailableForPurchase());
    }
}
