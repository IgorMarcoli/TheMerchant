<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'slug',
        'type', // 'cosmetic', 'service'
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
