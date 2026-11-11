<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** Status em que o item já foi adquirido pelo comprador. */
    public const PAID_STATUSES = ['pago', 'em_andamento', 'concluido'];

    protected $fillable = [
        'order_number',
        'checkout_key',
        'buyer_id',
        'total_amount',
        'currency',
        'status', // 'pendente', 'pago', 'em_andamento', 'concluido', 'cancelado'
        'notes',
        'reservation_expires_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'reservation_expires_at' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(Reservation::class, OrderItem::class);
    }

    public function totalCents(): int
    {
        return (int) round((float) $this->total_amount * 100);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'concluido';
    }
}
