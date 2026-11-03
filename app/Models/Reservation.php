<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    public const TTL_MINUTES = 15;

    protected $fillable = [
        'listing_id',
        'order_item_id',
        'quantity',
        'status', // 'active', 'confirmed', 'released'
        'expires_at',
        'released_reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Reservas que ainda bloqueiam saldo. Em now == expires_at a reserva já expirou.
     */
    public function scopeHolding(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }

    public function isHolding(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }
}
