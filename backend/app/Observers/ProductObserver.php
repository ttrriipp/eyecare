<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        $variant = $product->variants()->create([
            'is_default' => true,
            'price_adjustment' => 0,
        ]);

        $variant->inventory()->create([
            'quantity' => 0,
            'reorder_level' => 0,
            'notes' => null,
        ]);
    }
}
