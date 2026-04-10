<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('role', 'customer')->first();
        $staff = User::where('role', 'staff')->first();

        $products = Product::where('is_active', true)
            ->with('defaultVariant')
            ->orderBy('id')
            ->take(5)
            ->get();

        if (! $customer || $products->isEmpty()) {
            $this->command->warn('OrderSeeder skipped: no customer or products found.');

            return;
        }

        // Order definitions.
        // 'items' → array of [product_index, quantity]
        // 'discount_amount' → optional flat discount on the order total
        // 'bill_*' → bill-level fields
        $orders = [

            // ── Order 1: Completed — paid in cash; OR issued ──────────────────
            [
                'user_id' => $customer->id,
                'processed_by' => $staff?->id,
                'status' => OrderStatus::Completed,
                'discount_amount' => 0.00,
                'notes' => 'First test order — completed.',
                'completed_at' => now()->subDays(5),
                'ready_at' => now()->subDays(6),
                'items' => [
                    ['product_index' => 0, 'quantity' => 1],
                    ['product_index' => 1, 'quantity' => 2],
                ],
                'bill_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Cash,
                'collected_by' => $staff?->id,
            ],

            // ── Order 2: Pending — unpaid, not yet processed ───────────────────
            [
                'user_id' => $customer->id,
                'processed_by' => null,
                'status' => OrderStatus::Pending,
                'discount_amount' => 0.00,
                'notes' => null,
                'items' => [
                    ['product_index' => 2, 'quantity' => 1],
                ],
                'bill_status' => PaymentStatus::Unpaid,
                'payment_method' => null,
            ],

            // ── Order 3: Confirmed — senior citizen discount applied ───────────
            [
                'user_id' => $customer->id,
                'processed_by' => $staff?->id,
                'status' => OrderStatus::Confirmed,
                'discount_amount' => 150.00,
                'notes' => 'SC discount applied — 20% on frame.',
                'items' => [
                    ['product_index' => 0, 'quantity' => 1],
                    ['product_index' => 3, 'quantity' => 1],
                ],
                'bill_status' => PaymentStatus::Unpaid,
                'payment_method' => null,
            ],

            // ── Order 4: Walk-in — ready for pickup, paid via GCash ───────────
            [
                'user_id' => null,
                'walk_in_name' => 'Maria Santos',
                'walk_in_phone' => '09171234567',
                'processed_by' => $staff?->id,
                'status' => OrderStatus::ReadyForPickup,
                'discount_amount' => 0.00,
                'notes' => 'Walk-in customer — accessory ready for pickup.',
                'ready_at' => now()->subDay(),
                'items' => [
                    ['product_index' => 4, 'quantity' => 1],
                ],
                'bill_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::GCash,
                'collected_by' => $staff?->id,
            ],

            // ── Order 5: Completed — partial then full payment (Maya) ─────────
            [
                'user_id' => $customer->id,
                'processed_by' => $staff?->id,
                'status' => OrderStatus::Completed,
                'discount_amount' => 0.00,
                'notes' => null,
                'completed_at' => now()->subDays(2),
                'ready_at' => now()->subDays(3),
                'items' => [
                    ['product_index' => 3, 'quantity' => 1],
                ],
                'bill_status' => PaymentStatus::Paid,
                'payment_method' => PaymentMethod::Maya,
                'collected_by' => $staff?->id,
            ],
        ];

        $orderSeq = 1;
        $invoiceSeq = 1;

        foreach ($orders as $orderData) {
            $totalAmount = 0.00;
            $itemsPayload = [];

            foreach ($orderData['items'] as $itemData) {
                $product = $products[$itemData['product_index']] ?? $products->first();
                $variant = $product->defaultVariant;

                if (! $variant) {
                    continue;
                }

                $unitPrice = (float) $variant->unitPrice();
                $subtotal = $unitPrice * $itemData['quantity'];
                $totalAmount += $subtotal;

                $itemsPayload[] = [
                    'product_variant_id' => $variant->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $discount = $orderData['discount_amount'] ?? 0.00;
            $finalAmount = max(0, $totalAmount - $discount);

            $order = Order::create([
                'user_id' => $orderData['user_id'],
                'processed_by' => $orderData['processed_by'] ?? null,
                'walk_in_name' => $orderData['walk_in_name'] ?? null,
                'walk_in_phone' => $orderData['walk_in_phone'] ?? null,
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad($orderSeq, 5, '0', STR_PAD_LEFT),
                'status' => $orderData['status'],
                'total_amount' => $totalAmount,
                'discount_amount' => $discount,
                'notes' => $orderData['notes'],
                'ready_at' => $orderData['ready_at'] ?? null,
                'completed_at' => $orderData['completed_at'] ?? null,
            ]);

            foreach ($itemsPayload as $item) {
                $order->items()->create($item);
            }

            // Create bill
            $isPaid = $orderData['bill_status'] === PaymentStatus::Paid;

            Bill::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad($invoiceSeq, 5, '0', STR_PAD_LEFT),
                'amount' => $finalAmount,
                'amount_paid' => $isPaid ? $finalAmount : 0,
                'balance_due' => $isPaid ? 0 : $finalAmount,
                'payment_status' => $orderData['bill_status'],
                'payment_method' => $orderData['payment_method'] ?? null,
                'collected_by' => $orderData['collected_by'] ?? null,
                'paid_at' => $isPaid ? now() : null,
            ]);

            $orderSeq++;
            $invoiceSeq++;
        }
    }
}
