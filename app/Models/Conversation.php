<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'listing_id',
        'buyer_last_read_message_id',
        'seller_last_read_message_id',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function buyerLastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'buyer_last_read_message_id');
    }

    public function sellerLastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'seller_last_read_message_id');
    }

    public function isParticipant(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->seller_id === $user->id;
    }

    public function getOtherParticipant(User $user): ?User
    {
        if ($this->buyer_id === $user->id) {
            return $this->seller;
        }

        if ($this->seller_id === $user->id) {
            return $this->buyer;
        }

        return null;
    }

    public function unreadCountFor(User $user): int
    {
        if (! $this->isParticipant($user)) {
            return 0;
        }

        $lastReadId = $this->buyer_id === $user->id
            ? ($this->buyer_last_read_message_id ?? 0)
            : ($this->seller_last_read_message_id ?? 0);

        return $this->messages()
            ->where('id', '>', $lastReadId)
            ->where('sender_id', '!=', $user->id)
            ->count();
    }
}

