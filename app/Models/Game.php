<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cover_image',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

return Storage::disk('public')->url($this->cover_image);
    }
}
