<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'method',
        'transaction_id',
        'status', // 'pending', 'paid', 'expired', 'cancelled', 'failed', 'refunded', 'disputed'
        'dev_mode',
        'amount',
        'idempotency_key',
        'payload',
        'br_code',
        'br_code_base64',
        'expires_at',
        'paid_at',
        'refund_status', // null, 'pending', 'refunded', 'failed'
        'refund_id',
        'refund_attempts',
        'last_error',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'dev_mode' => 'boolean',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'refund_attempts' => 'integer',
    ];

    protected $hidden = [
        'br_code_base64',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function amountCents(): int
    {
        return (int) round((float) $this->amount * 100);
    }
}
