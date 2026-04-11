<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('role', 'customer')->first();
        $admin = User::where('role', 'admin')->first();

        $products = Product::where('is_active', true)->orderBy('id')->take(5)->get();

        if (! $customer || $products->isEmpty()) {
            $this->command->warn('FeedbackSeeder skipped: no customer or products found.');

            return;
        }

        // Indices follow ProductSeeder order: frame, lens, contacts, sunglasses, accessory.
        // Indices 0–1 align with the first completed order (frame + lens) for verified purchases.
        $reviews = [
            [
                'product_index' => 0,
                'rating' => 5,
                'comment' => 'Excellent quality frames! Very comfortable to wear all day. The acetate feels premium.',
                'is_verified_purchase' => true,
                'is_visible' => true,
                'admin_reply' => 'Thank you for the kind words, Juan! We\'re glad the Classic Full-Rim is working out well for you. Visit us anytime for adjustments.',
                'moderated_by' => $admin?->id,
                'moderated_at' => now()->subDays(4),
            ],
            [
                'product_index' => 1,
                'rating' => 4,
                'comment' => 'Clear vision and the anti-radiation coating seems to help with screen time. Lab turnaround was quick.',
                'is_verified_purchase' => true,
                'is_visible' => true,
            ],
            [
                'product_index' => 2,
                'rating' => 3,
                'comment' => 'Comfortable dailies; took a few days to get used to insertion. Stock was fresh.',
                'is_verified_purchase' => false,
                'is_visible' => true,
            ],
            [
                'product_index' => 3,
                'rating' => 5,
                'comment' => null,
                'is_verified_purchase' => false,
                'is_visible' => true,
            ],
            [
                'product_index' => 4,
                'rating' => 4,
                'comment' => 'Soft cloth, no streaks on my lenses. Good size for my bag.',
                'is_verified_purchase' => false,
                'is_visible' => true,
            ],
        ];

        foreach ($reviews as $review) {
            $product = $products[$review['product_index']] ?? $products->first();

            Feedback::updateOrCreate(
                ['user_id' => $customer->id, 'product_id' => $product->id],
                [
                    'feedback_type' => 'product',
                    'appointment_id' => null,
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'is_verified_purchase' => $review['is_verified_purchase'],
                    'is_visible' => $review['is_visible'],
                    'approval_status' => 'approved',
                    'approval_reviewed_at' => null,
                    'approval_reviewed_by' => null,
                    'rejection_reason' => null,
                    'admin_reply' => $review['admin_reply'] ?? null,
                    'moderated_by' => $review['moderated_by'] ?? null,
                    'moderated_at' => $review['moderated_at'] ?? null,
                ],
            );
        }
    }
}
