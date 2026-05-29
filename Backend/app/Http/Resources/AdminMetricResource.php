<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMetricResource extends JsonResource
{
    /**
     * Transform the metrics array into the API contract.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'totals' => $this->totals,
            'by_status' => $this->by_status,
            'by_day' => $this->by_day,
            'top_doctors' => $this->top_doctors,
        ];
    }
}
