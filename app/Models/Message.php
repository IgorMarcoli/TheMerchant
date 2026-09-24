<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'client_uuid',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Serialized safe array respecting RNF07 privacy (no emails or sensitive credentials).
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name ?? 'Usuário',
            'client_uuid' => $this->client_uuid,
            'body' => $this->body,
            'created_at' => $this->created_at?->toISOString(),
            'formatted_time' => $this->created_at?->format('H:i') ?? '',
            'formatted_date' => $this->created_at?->format('d/m/Y') ?? '',
        ];
    }
}

