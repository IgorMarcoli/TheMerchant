<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAudit extends Model
{
    protected $fillable = [
        'report_id',
        'moderator_id',
        'listing_id',
        'action',
        'previous_report_status',
        'new_report_status',
        'previous_listing_status',
        'new_listing_status',
        'resolution_notes',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
