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
        $products = Product::where('is_active', true)->take(5)->get();

        if (! $customer || $products->isEmpty()) {
            $this->command->warn('FeedbackSeeder skipped: no customer or products found.');

            return;
        }

        $reviews = [
            ['product_index' => 0, 'rating' => 5, 'comment' => 'Excellent quality frames! Very comfortable to wear all day.'],
            ['product_index' => 1, 'rating' => 4, 'comment' => 'Good product, fast service. Would buy again.'],
            ['product_index' => 2, 'rating' => 3, 'comment' => 'Decent but expected better for the price.'],
            ['product_index' => 3, 'rating' => 5, 'comment' => null],
            ['product_index' => 4, 'rating' => 4, 'comment' => 'Great value for money!'],
        ];

        foreach ($reviews as $review) {
            $product = $products[$review['product_index']] ?? $products->first();

            Feedback::updateOrCreate(
                ['user_id' => $customer->id, 'product_id' => $product->id],
                ['rating' => $review['rating'], 'comment' => $review['comment']],
            );
        }
    }
}
