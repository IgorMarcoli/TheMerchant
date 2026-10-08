<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemDelivery extends Model
{
    protected $fillable = [
        'order_item_id',
        'delivery_number',
        'recorded_by',
        'delivered_at',
    ];

    protected $casts = [
        'delivery_number' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
