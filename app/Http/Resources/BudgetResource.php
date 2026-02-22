<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'category_id'    => $this->category_id,
            'amount'         => (float) $this->amount,
            'spent'          => (float) $this->spent,
            'month'          => $this->month,
            'year'           => $this->year,
            'category_name'  => $this->category?->name,
            'category_icon'  => $this->category?->icon,
            'category_color' => $this->category?->color,
        ];
    }
}
