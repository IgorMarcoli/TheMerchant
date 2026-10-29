<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'listing_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function subtotalCents(): int
    {
        return (int) round((float) $this->unit_price * 100) * $this->quantity;
    }

    public function subtotal(): float
    {
        return $this->subtotalCents() / 100;
    }

    public function isAvailableForPurchase(): bool
    {
        if ($this->listing === null || ! $this->listing->isAvailable()) {
            return false;
        }

        if ($this->listing->category?->type !== 'service') {
            return $this->quantity === 1 && $this->listing->capacity >= 1;
        }

        return $this->quantity >= 1 && $this->quantity <= $this->listing->capacity;
    }

    public function hasPriceChanged(): bool
    {
        if ($this->listing === null) {
            return false;
        }

        return (int) round((float) $this->unit_price * 100) !== (int) round((float) $this->listing->price * 100);
    }

    public function unavailableReason(): ?string
    {
        if ($this->listing === null || ! $this->listing->isAvailable()) {
            return 'Este anúncio não está mais disponível para compra. Remova-o para prosseguir.';
        }

        if ($this->listing->category?->type !== 'service' && $this->quantity !== 1) {
            return 'Cosméticos possuem quantidade única (1 unidade).';
        }

        if ($this->quantity > $this->listing->capacity) {
            return "Quantidade indisponível no momento (máximo de {$this->listing->capacity} sessão(ões) disponível(eis)).";
        }

        return null;
    }
}
