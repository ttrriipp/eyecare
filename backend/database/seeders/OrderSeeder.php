<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
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
        $products = Product::where('is_active', true)->with('defaultVariant')->take(5)->get();

        if (! $customer || $products->isEmpty()) {
            $this->command->warn('OrderSeeder skipped: no customer or products found.');

            return;
        }

        $orders = [
            // Completed order (bill paid)
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::Completed,
                'notes' => 'First test order – completed.',
                'bill_status' => PaymentStatus::Paid,
                'payment_method' => 'cash',
                'items' => [
                    ['product_index' => 0, 'quantity' => 1],
                    ['product_index' => 1, 'quantity' => 2],
                ],
            ],
            // Pending order (bill unpaid)
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'notes' => null,
                'bill_status' => PaymentStatus::Unpaid,
                'payment_method' => null,
                'items' => [
                    ['product_index' => 2, 'quantity' => 1],
                ],
            ],
            // Confirmed order (bill unpaid)
            [
                'user_id' => $customer->id,
                'status' => OrderStatus::Confirmed,
                'notes' => 'Rush order please.',
                'bill_status' => PaymentStatus::Unpaid,
                'payment_method' => null,
                'items' => [
                    ['product_index' => 0, 'quantity' => 1],
                    ['product_index' => 3, 'quantity' => 1],
                ],
            ],
            // Walk-in order (ready for pickup, bill paid)
            [
                'user_id' => null,
                'walk_in_name' => 'Maria Santos',
                'walk_in_phone' => '09171234567',
                'status' => OrderStatus::ReadyForPickup,
                'notes' => 'Walk-in customer.',
                'bill_status' => PaymentStatus::Paid,
                'payment_method' => 'GCash',
                'items' => [
                    ['product_index' => 4, 'quantity' => 1],
                ],
            ],
        ];

        $orderNumber = 1;
        $invoiceNumber = 1;

        foreach ($orders as $orderData) {
            $totalAmount = 0;
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

            $order = Order::create([
                'user_id' => $orderData['user_id'],
                'walk_in_name' => $orderData['walk_in_name'] ?? null,
                'walk_in_phone' => $orderData['walk_in_phone'] ?? null,
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad($orderNumber, 5, '0', STR_PAD_LEFT),
                'status' => $orderData['status'],
                'total_amount' => $totalAmount,
                'notes' => $orderData['notes'],
            ]);

            foreach ($itemsPayload as $item) {
                $order->items()->create($item);
            }

            // Create bill for the order
            Bill::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad($invoiceNumber, 5, '0', STR_PAD_LEFT),
                'amount' => $totalAmount,
                'payment_status' => $orderData['bill_status'],
                'payment_method' => $orderData['payment_method'],
                'paid_at' => $orderData['bill_status'] === PaymentStatus::Paid ? now() : null,
            ]);

            $orderNumber++;
            $invoiceNumber++;
        }
    }
}
