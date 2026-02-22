<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'type'    => $this->type,
            'balance' => (float) $this->balance,
            'icon'    => $this->icon,
            'color'   => $this->color,
        ];
    }
}
