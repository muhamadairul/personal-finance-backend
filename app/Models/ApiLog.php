<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'payload',
        'response',
        'status_code',
        'ip_address',
        'user_agent',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'response'    => 'array',
            'status_code' => 'integer',
            'duration_ms' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
