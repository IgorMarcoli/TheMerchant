<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'buyer_id',
        'total_amount',
        'status', // 'pendente', 'pago', 'em_andamento', 'concluido', 'cancelado'
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
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
        return $this->hasOne(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['pago', 'em_andamento', 'concluido']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'concluido';
    }
}
