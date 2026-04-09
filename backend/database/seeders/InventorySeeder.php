<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Keyed by product name.
        // Fields: quantity, reorder_level, reorder_quantity, batch_number,
        //         expires_at, storage_location, notes
        // expires_at is required for the 'Contact Lenses' category.
        $byName = [
            'Classic Full-Rim Frame' => [
                'quantity'         => 28,
                'reorder_level'    => 8,
                'reorder_quantity' => 20,
                'storage_location' => 'Shelf A1',
                'notes'            => 'Popular frame; restock monthly.',
            ],
            'Titanium Semi-Rimless Frame' => [
                'quantity'         => 14,
                'reorder_level'    => 6,
                'reorder_quantity' => 15,
                'storage_location' => 'Shelf A2',
            ],
            'TR90 Flexible Frame' => [
                'quantity'         => 22,
                'reorder_level'    => 7,
                'reorder_quantity' => 15,
                'storage_location' => 'Shelf A3',
            ],
            'Single Vision Anti-Radiation Lens' => [
                'quantity'         => 60,
                'reorder_level'    => 15,
                'reorder_quantity' => 30,
                'storage_location' => 'Cabinet B1',
            ],
            'Progressive Photochromic Lens' => [
                'quantity'         => 18,
                'reorder_level'    => 5,
                'reorder_quantity' => 10,
                'storage_location' => 'Cabinet B2',
                'notes'            => 'Long lead time from lab; order early.',
            ],
            'Bifocal Lens' => [
                'quantity'         => 25,
                'reorder_level'    => 8,
                'reorder_quantity' => 15,
                'storage_location' => 'Cabinet B3',
            ],
            // Contact lenses: batch_number and expires_at are required
            'Daily Disposable Clear Contacts' => [
                'quantity'         => 80,
                'reorder_level'    => 20,
                'reorder_quantity' => 50,
                'batch_number'     => 'ACU-2026-03-D',
                'expires_at'       => '2027-03-31',
                'storage_location' => 'Cabinet C1',
                'notes'            => 'Keep sealed until dispensed.',
            ],
            'Monthly Hydrogel Contact Lenses' => [
                'quantity'         => 45,
                'reorder_level'    => 12,
                'reorder_quantity' => 25,
                'batch_number'     => 'ACU-2026-02-M',
                'expires_at'       => '2027-02-28',
                'storage_location' => 'Cabinet C2',
            ],
            'Polarized UV Protection Sunglasses' => [
                'quantity'         => 10,
                'reorder_level'    => 4,
                'reorder_quantity' => 12,
                'storage_location' => 'Shelf D1',
            ],
            // Accessories — low stock on cloths is intentional for demo
            'Premium Microfiber Cleaning Cloth' => [
                'quantity'         => 3,
                'reorder_level'    => 10,
                'reorder_quantity' => 30,
                'storage_location' => 'Drawer E1',
                'notes'            => 'Low stock — reorder cloths.',
            ],
            'Hard Shell Eyeglass Case' => [
                'quantity'         => 35,
                'reorder_level'    => 12,
                'reorder_quantity' => 25,
                'storage_location' => 'Shelf E2',
            ],
            'Lens Cleaning Solution 120ml' => [
                'quantity'         => 48,
                'reorder_level'    => 15,
                'reorder_quantity' => 20,
                'storage_location' => 'Shelf E3',
            ],
        ];

        foreach (Product::query()->orderBy('id')->get() as $product) {
            $payload = $byName[$product->name] ?? [
                'quantity'         => 20,
                'reorder_level'    => 5,
                'reorder_quantity' => 10,
                'notes'            => null,
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
