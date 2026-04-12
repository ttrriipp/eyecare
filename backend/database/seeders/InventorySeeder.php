<?php

namespace Database\Seeders;

use App\Enums\InventoryAdjustmentReason;
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

        // Keyed by product name (must match ProductSeeder).
        // Fields: quantity (final on-hand target), reorder_level, reorder_quantity, batch_number,
        //         expires_at, notes
        // expires_at / batch_number used for categories that require expiry (e.g. contact lenses).
        $byName = [
            'Classic Full-Rim Frame' => [
                'quantity' => 28,
                'reorder_level' => 8,
                'reorder_quantity' => 20,
                'notes' => 'Popular frame; restock monthly.',
            ],
            'Daily Disposable Clear Contacts' => [
                'quantity' => 80,
                'reorder_level' => 20,
                'reorder_quantity' => 50,
                'batch_number' => 'ACU-2026-03-D',
                'expires_at' => '2027-03-31',
                'notes' => 'Keep sealed until dispensed.',
            ],
            'Polarized UV Protection Sunglasses' => [
                'quantity' => 10,
                'reorder_level' => 4,
                'reorder_quantity' => 12,
            ],
            'Premium Microfiber Cleaning Cloth' => [
                'quantity' => 120,
                'reorder_level' => 24,
                'reorder_quantity' => 60,
                'notes' => 'Fast-moving accessory.',
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

            $meta = array_merge($payload, ['quantity' => 0]);
            Inventory::updateOrCreate(
                ['product_variant_id' => $variant->id],
                $meta,
            );

            if ($product->name === 'Classic Full-Rim Frame') {
                $inventoryService->adjust($variant, 'set', 30, InventoryAdjustmentReason::DataCorrection->value, null, $adjustedBy);
                $inventoryService->adjust($variant, 'remove', 2, InventoryAdjustmentReason::DamagedOrDefective->value, null, $adjustedBy);
            } else {
                $inventoryService->adjust($variant, 'set', $targetQty, InventoryAdjustmentReason::DataCorrection->value, null, $adjustedBy);
            }
        }
    }
}
