<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $adjustedBy = User::where('role', UserRole::Staff)->value('id');

        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        // Keyed by product name.
        // Fields: quantity (final on-hand target), reorder_level, reorder_quantity, batch_number,
        //         expires_at, notes
        // expires_at is required for the 'Contact Lenses' category.
        $byName = [
            'Classic Full-Rim Frame' => [
                'quantity' => 28,
                'reorder_level' => 8,
                'reorder_quantity' => 20,
                'notes' => 'Popular frame; restock monthly.',
            ],
            'Titanium Semi-Rimless Frame' => [
                'quantity' => 14,
                'reorder_level' => 6,
                'reorder_quantity' => 15,
            ],
            'TR90 Flexible Frame' => [
                'quantity' => 22,
                'reorder_level' => 7,
                'reorder_quantity' => 15,
            ],
            'Single Vision Anti-Radiation Lens' => [
                'quantity' => 60,
                'reorder_level' => 15,
                'reorder_quantity' => 30,
            ],
            'Progressive Photochromic Lens' => [
                'quantity' => 18,
                'reorder_level' => 5,
                'reorder_quantity' => 10,
                'notes' => 'Long lead time from lab; order early.',
            ],
            'Bifocal Lens' => [
                'quantity' => 25,
                'reorder_level' => 8,
                'reorder_quantity' => 15,
            ],
            'Daily Disposable Clear Contacts' => [
                'quantity' => 80,
                'reorder_level' => 20,
                'reorder_quantity' => 50,
                'batch_number' => 'ACU-2026-03-D',
                'expires_at' => '2027-03-31',
                'notes' => 'Keep sealed until dispensed.',
            ],
            'Monthly Hydrogel Contact Lenses' => [
                'quantity' => 45,
                'reorder_level' => 12,
                'reorder_quantity' => 25,
                'batch_number' => 'ACU-2026-02-M',
                'expires_at' => '2027-02-28',
            ],
            'Polarized UV Protection Sunglasses' => [
                'quantity' => 10,
                'reorder_level' => 4,
                'reorder_quantity' => 12,
            ],
            'Premium Microfiber Cleaning Cloth' => [
                'quantity' => 3,
                'reorder_level' => 10,
                'reorder_quantity' => 30,
                'notes' => 'Low stock — reorder cloths.',
            ],
            'Hard Shell Eyeglass Case' => [
                'quantity' => 35,
                'reorder_level' => 12,
                'reorder_quantity' => 25,
            ],
            'Lens Cleaning Solution 120ml' => [
                'quantity' => 48,
                'reorder_level' => 15,
                'reorder_quantity' => 20,
            ],
        ];

        foreach (Product::query()->orderBy('id')->get() as $product) {
            $payload = $byName[$product->name] ?? [
                'quantity' => 20,
                'reorder_level' => 5,
                'reorder_quantity' => 10,
                'notes' => null,
            ];

            $variant = $product->defaultVariant;
            if (! $variant) {
                continue;
            }

            $targetQty = (int) $payload['quantity'];

            // Start at zero so each final quantity is explained by adjustment history (plain reason codes, no ": notes").
            $meta = array_merge($payload, ['quantity' => 0]);
            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                $meta,
            );

            if ($product->name === 'Classic Full-Rim Frame') {
                // Demo: two-step trail (30 in, 2 damaged) → 28 on hand
                $inventoryService->adjust($variant, 'set', 30, 'initial_count', null, $adjustedBy);
                $inventoryService->adjust($variant, 'remove', 2, 'damaged', null, $adjustedBy);
            } else {
                $inventoryService->adjust($variant, 'set', $targetQty, 'initial_count', null, $adjustedBy);
            }
        }
    }
}
