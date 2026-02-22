<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'category_id'    => $this->category_id,
            'wallet_id'      => $this->wallet_id,
            'note'           => $this->note,
            'date'           => $this->date->format('Y-m-d'),
            'created_at'     => $this->created_at?->toISOString(),
            'category_name'  => $this->category?->name,
            'category_icon'  => $this->category?->icon,
            'category_color' => $this->category?->color,
            'wallet_name'    => $this->wallet?->name,
        ];
    }
}
