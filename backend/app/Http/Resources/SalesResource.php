<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pon_name' =>  null,
            'sale_date' => null,
            'item_code' => null,
            'item_name' => null,
            'price_per_quantity' => null,
            'total_price' => $this->NetTotal,
            'quantity' => null,
        ];
    }
}
