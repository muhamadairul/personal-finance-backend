<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppLog extends Model
{
    protected $fillable = [
        'level',
        'channel',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }
}
