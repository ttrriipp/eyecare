<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $bySku = [
            'FRM-001' => ['quantity' => 28, 'reorder_level' => 8, 'notes' => 'Popular frame; restock monthly.'],
            'FRM-002' => ['quantity' => 14, 'reorder_level' => 6, 'notes' => null],
            'FRM-003' => ['quantity' => 22, 'reorder_level' => 7, 'notes' => null],
            'LNS-001' => ['quantity' => 60, 'reorder_level' => 15, 'notes' => null],
            'LNS-002' => ['quantity' => 18, 'reorder_level' => 5, 'notes' => 'Long lead time from lab.'],
            'CTL-001' => ['quantity' => 80, 'reorder_level' => 20, 'notes' => null],
            'SUN-001' => ['quantity' => 10, 'reorder_level' => 4, 'notes' => null],
            'ACC-001' => ['quantity' => 3, 'reorder_level' => 10, 'notes' => 'Low stock — reorder cloths.'],
            'ACC-002' => ['quantity' => 35, 'reorder_level' => 12, 'notes' => null],
            'ACC-003' => ['quantity' => 48, 'reorder_level' => 15, 'notes' => null],
        ];

        foreach (Product::query()->orderBy('id')->get() as $product) {
            $payload = $bySku[$product->sku] ?? [
                'quantity' => 20,
                'reorder_level' => 5,
                'notes' => null,
            ];

            Inventory::updateOrCreate(
                ['product_id' => $product->id],
                $payload,
            );
        }
    }
}
