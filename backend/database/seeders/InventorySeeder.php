<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $byName = [
            'Classic Full-Rim Frame' => ['quantity' => 28, 'reorder_level' => 8, 'notes' => 'Popular frame; restock monthly.'],
            'Titanium Semi-Rimless Frame' => ['quantity' => 14, 'reorder_level' => 6, 'notes' => null],
            'TR90 Flexible Frame' => ['quantity' => 22, 'reorder_level' => 7, 'notes' => null],
            'Single Vision Anti-Radiation Lens' => ['quantity' => 60, 'reorder_level' => 15, 'notes' => null],
            'Progressive Photochromic Lens' => ['quantity' => 18, 'reorder_level' => 5, 'notes' => 'Long lead time from lab.'],
            'Daily Disposable Clear Contacts' => ['quantity' => 80, 'reorder_level' => 20, 'notes' => null],
            'Polarized UV Protection Sunglasses' => ['quantity' => 10, 'reorder_level' => 4, 'notes' => null],
            'Premium Microfiber Cleaning Cloth' => ['quantity' => 3, 'reorder_level' => 10, 'notes' => 'Low stock — reorder cloths.'],
            'Hard Shell Eyeglass Case' => ['quantity' => 35, 'reorder_level' => 12, 'notes' => null],
            'Lens Cleaning Solution 120ml' => ['quantity' => 48, 'reorder_level' => 15, 'notes' => null],
        ];

        foreach (Product::query()->orderBy('id')->get() as $product) {
            $payload = $byName[$product->name] ?? [
                'quantity' => 20,
                'reorder_level' => 5,
                'notes' => null,
            ];

            $variant = $product->defaultVariant;
            if (! $variant) {
                continue;
            }

            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                $payload,
            );
        }
    }
}
