<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'reputation_score',
        'total_reviews',
        'total_sales',
    ];

    protected $casts = [
        'reputation_score' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
