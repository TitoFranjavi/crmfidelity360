<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        return [
            '_id'   => (string) $this->_id,
            'name' => $this->name,

            'products' => [
                'electricity' => $this->removeCommissions(
                    $this->products['electricity'] ?? []
                ),
                'gas' => $this->removeCommissions(
                    $this->products['gas'] ?? []
                ),
            ],
            'fees' => $this->fees,
            'logo' => $this->logo,
            'createdBy' => $this->createdBy,
            'surplus' => $this->surplus
        ];
    }

    private function removeCommissions(array $products): array
    {
        return collect($products)
            ->map(function ($product) {
                if (isset($product['fees']) && is_array($product['fees'])) {
                    $product['fees'] = collect($product['fees'])
                        ->map(function ($fee) {
                            unset(
                                $fee['consumptionBasic'],
                                $fee['consumptionBreakdown']
                            );

                            return $fee;
                        })
                        ->values()
                        ->toArray();
                }

                return $product;
            })
            ->values()
            ->toArray();
    }
}
