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
        $admin    = User::where('role', 'admin')->first();

        $products = Product::where('is_active', true)->take(6)->get();

        if (! $customer || $products->isEmpty()) {
            $this->command->warn('FeedbackSeeder skipped: no customer or products found.');

            return;
        }

        // Products at indices 0 and 1 were in the completed order by this customer
        // (Classic Full-Rim Frame and Titanium Semi-Rimless Frame), so those are
        // verified purchases. The rest are unverified.
        $reviews = [
            [
                'product_index'       => 0,
                'rating'              => 5,
                'comment'             => 'Excellent quality frames! Very comfortable to wear all day. The acetate feels premium.',
                'is_verified_purchase'=> true,
                'is_visible'          => true,
                'admin_reply'         => 'Thank you for the kind words, Juan! We\'re glad the Classic Full-Rim is working out well for you. Visit us anytime for adjustments.',
                'moderated_by'        => $admin?->id,
                'moderated_at'        => now()->subDays(4),
            ],
            [
                'product_index'       => 1,
                'rating'              => 4,
                'comment'             => 'Good product and fast service. The titanium really is lighter than my old frames. Would buy again.',
                'is_verified_purchase'=> true,
                'is_visible'          => true,
            ],
            [
                'product_index'       => 2,
                'rating'              => 3,
                'comment'             => 'Decent frame but expected the color to be a bit deeper. Fit is comfortable though.',
                'is_verified_purchase'=> false,
                'is_visible'          => true,
            ],
            [
                'product_index'       => 3,
                'rating'              => 5,
                'comment'             => null,
                'is_verified_purchase'=> false,
                'is_visible'          => true,
            ],
            [
                'product_index'       => 4,
                'rating'              => 4,
                'comment'             => 'Great value for money! The photochromic coating works well both indoors and outdoors.',
                'is_verified_purchase'=> false,
                'is_visible'          => true,
            ],
        ];

        foreach ($reviews as $review) {
            $product = $products[$review['product_index']] ?? $products->first();

            Feedback::updateOrCreate(
                ['user_id' => $customer->id, 'product_id' => $product->id],
                [
                    'rating'               => $review['rating'],
                    'comment'              => $review['comment'],
                    'is_verified_purchase' => $review['is_verified_purchase'],
                    'is_visible'           => $review['is_visible'],
                    'admin_reply'          => $review['admin_reply'] ?? null,
                    'moderated_by'         => $review['moderated_by'] ?? null,
                    'moderated_at'         => $review['moderated_at'] ?? null,
                ],
            );
        }
    }
}
