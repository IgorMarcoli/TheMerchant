<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'listing_id',
        'listing_title',
        'listing_description',
        'listing_type',
        'game_id',
        'game_name',
        'category_id',
        'category_name',
        'seller_id',
        'unit_price',
        'currency',
        'quantity',
        'session_duration_minutes',
        'delivery_instructions',
        'delivery_conditions',
        'scheduling_mode', // 'by_chat', 'not_applicable'
        'timezone',
        'scheduled_at',
        'delivery_status', // 'aguardando_pagamento', 'em_entrega', 'entregue'
        'delivered_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'session_duration_minutes' => 'integer',
        'scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderItemDelivery::class)->orderBy('delivery_number');
    }
}
