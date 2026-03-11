<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Eyeglass Frames', 'slug' => 'eyeglass-frames', 'description' => 'Prescription eyeglass frames in various styles and materials'],
            ['name' => 'Prescription Lenses', 'slug' => 'prescription-lenses', 'description' => 'Single vision, bifocal, and progressive lenses with various coatings'],
            ['name' => 'Contact Lenses', 'slug' => 'contact-lenses', 'description' => 'Daily, monthly, and colored contact lenses'],
            ['name' => 'Sunglasses', 'slug' => 'sunglasses', 'description' => 'Prescription and non-prescription sunglasses'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Cases, cleaning solutions, cloths, and other accessories'],
        ];

        foreach ($categories as $categoryData) {
            ProductCategory::create($categoryData);
        }

        $frames = ProductCategory::where('slug', 'eyeglass-frames')->first();
        $lenses = ProductCategory::where('slug', 'prescription-lenses')->first();
        $contacts = ProductCategory::where('slug', 'contact-lenses')->first();
        $sunglasses = ProductCategory::where('slug', 'sunglasses')->first();
        $accessories = ProductCategory::where('slug', 'accessories')->first();

        $products = [
            [
                'category_id' => $frames->id,
                'name' => 'Classic Full-Rim Frame',
                'description' => 'Durable acetate full-rim frame suitable for everyday wear.',
                'price' => 1500.00,
                'sku' => 'FRM-001',
                'brand' => 'Bolon',
                'frame_material' => 'Acetate',
            ],
            [
                'category_id' => $frames->id,
                'name' => 'Titanium Semi-Rimless Frame',
                'description' => 'Lightweight titanium semi-rimless frame for a sleek look.',
                'price' => 2800.00,
                'sku' => 'FRM-002',
                'brand' => 'Hangten',
                'frame_material' => 'Titanium',
            ],
            [
                'category_id' => $frames->id,
                'name' => 'TR90 Flexible Frame',
                'description' => 'Super flexible TR90 frame, ideal for active lifestyles.',
                'price' => 1200.00,
                'sku' => 'FRM-003',
                'brand' => 'Peculiar',
                'frame_material' => 'TR90',
            ],
            [
                'category_id' => $lenses->id,
                'name' => 'Single Vision Anti-Radiation Lens',
                'description' => 'Single vision lens with anti-radiation and blue-light blocking coating.',
                'price' => 800.00,
                'sku' => 'LNS-001',
                'lens_type' => 'Single Vision',
            ],
            [
                'category_id' => $lenses->id,
                'name' => 'Progressive Photochromic Lens',
                'description' => 'Progressive lens with photochromic (Transitions) coating.',
                'price' => 3500.00,
                'sku' => 'LNS-002',
                'lens_type' => 'Progressive',
            ],
            [
                'category_id' => $contacts->id,
                'name' => 'Daily Disposable Clear Contacts',
                'description' => 'Pack of 30 daily disposable clear contact lenses.',
                'price' => 1200.00,
                'sku' => 'CTL-001',
                'brand' => 'Acuvue',
                'lens_type' => 'Daily',
            ],
            [
                'category_id' => $sunglasses->id,
                'name' => 'Polarized UV Protection Sunglasses',
                'description' => 'Stylish polarized sunglasses with full UV protection.',
                'price' => 2000.00,
                'sku' => 'SUN-001',
                'brand' => 'Bolon',
                'frame_material' => 'Acetate',
            ],
            [
                'category_id' => $accessories->id,
                'name' => 'Premium Microfiber Cleaning Cloth',
                'description' => 'Soft microfiber cloth for lens cleaning.',
                'price' => 50.00,
                'sku' => 'ACC-001',
            ],
            [
                'category_id' => $accessories->id,
                'name' => 'Hard Shell Eyeglass Case',
                'description' => 'Durable hard shell case for eyeglass protection.',
                'price' => 250.00,
                'sku' => 'ACC-002',
            ],
            [
                'category_id' => $accessories->id,
                'name' => 'Lens Cleaning Solution 120ml',
                'description' => 'Anti-fog lens cleaning spray solution.',
                'price' => 150.00,
                'sku' => 'ACC-003',
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }
    }
}
