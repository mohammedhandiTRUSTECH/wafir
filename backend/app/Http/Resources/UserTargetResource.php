<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserTargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = $this->target;
        return [
            'name' => $target->name,
            'target' => auth()->user()->userTarget(),
            'rules' => TargetRuleResource::collection($target->details)
        ];
    }
}
