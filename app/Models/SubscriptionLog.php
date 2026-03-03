<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'    => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
