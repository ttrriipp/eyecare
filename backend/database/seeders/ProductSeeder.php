<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    /** @var array<int, string> */
    private const DURATION_OPTIONS = ['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'];

    public function run(): void
    {
        // ── Categories ────────────────────────────────────────────────────────
        $categories = [
            [
                'slug' => 'eyeglass-frames',
                'name' => 'Eyeglass Frames',
                'description' => 'Prescription eyeglass frames in various styles and materials.',
                'has_ar_support' => true,
                'requires_expiry_tracking' => false,
                'requires_prescription' => false,
                'stock_unit' => 'units',
                'is_system' => true,
                'has_frame_size' => true,
                'has_color' => true,
                'has_material' => true,
                'has_lens_type' => true,
                'has_power_field' => false,
                'has_duration' => false,
            ],
            [
                'slug' => 'contact-lenses',
                'name' => 'Contact Lenses',
                'description' => 'Daily, monthly, and colored contact lenses. Expiry date required.',
                'has_ar_support' => false,
                'requires_expiry_tracking' => true,
                'requires_prescription' => true,
                'stock_unit' => 'boxes',
                'is_system' => true,
                'has_frame_size' => false,
                'has_color' => false,
                'has_material' => false,
                'has_lens_type' => false,
                'has_power_field' => true,
                'has_duration' => true,
            ],
            [
                'slug' => 'sunglasses',
                'name' => 'Sunglasses',
                'description' => 'Prescription and non-prescription sunglasses with UV protection.',
                'has_ar_support' => true,
                'requires_expiry_tracking' => false,
                'requires_prescription' => false,
                'stock_unit' => 'units',
                'is_system' => true,
                'has_frame_size' => true,
                'has_color' => true,
                'has_material' => true,
                'has_lens_type' => true,
                'has_power_field' => false,
                'has_duration' => false,
            ],
            [
                'slug' => 'accessories',
                'name' => 'Accessories',
                'description' => 'Cases, cleaning solutions, cloths, and other eyewear accessories.',
                'has_ar_support' => false,
                'requires_expiry_tracking' => false,
                'requires_prescription' => false,
                'stock_unit' => 'units',
                'is_system' => false,
                'has_frame_size' => false,
                'has_color' => true,
                'has_material' => false,
                'has_lens_type' => false,
                'has_power_field' => false,
                'has_duration' => false,
            ],
        ];

        foreach ($categories as $categoryData) {
            $slug = $categoryData['slug'];
            $category = ProductCategory::withoutGlobalScopes()->firstOrNew(['slug' => $slug]);
            $category->forceFill($categoryData)->save();
        }

        $frames = ProductCategory::where('slug', 'eyeglass-frames')->first();
        $contacts = ProductCategory::where('slug', 'contact-lenses')->first();
        $sunglasses = ProductCategory::where('slug', 'sunglasses')->first();
        $accessories = ProductCategory::where('slug', 'accessories')->first();

        // Four sample products — default_variant fields must match category flags (admin forms).
        $products = [

            // 1. Eyeglass frame: color, frame size, material, lens type (+ optional AR)
            [
                'product' => [
                    'category_id' => $frames->id,
                    'name' => 'Classic Full-Rim Frame',
                    'description' => 'Durable acetate full-rim frame suitable for everyday wear. '
                        .'Available in multiple colours and well-suited for high prescriptions.',
                    'price' => 1500.00,
                    'brand' => 'Bolon',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'color' => 'Black',
                    'frame_size' => 'Medium (54mm)',
                    'material' => 'Acetate',
                    'lens_type' => 'Clear',
                    'cost_per_unit' => 600.00,
                    'ar_model_url' => 'https://models.eyecare.test/frames/classic-full-rim.glb',
                ],
            ],

            // 2. Contacts: power + duration (mapped to category behavior flags)
            [
                'product' => [
                    'category_id' => $contacts->id,
                    'name' => 'Daily Disposable Clear Contacts',
                    'description' => 'Pack of 30 daily disposable clear contact lenses. '
                        .'Recommended for first-time wearers and occasional use.',
                    'price' => 1200.00,
                    'brand' => 'Acuvue',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'power' => '-2.00',
                    'duration' => 'Daily',
                    'cost_per_unit' => 480.00,
                ],
            ],

            // 3. Sunglasses: same shape as frames + sunglass lens options
            [
                'product' => [
                    'category_id' => $sunglasses->id,
                    'name' => 'Polarized UV Protection Sunglasses',
                    'description' => 'Stylish polarized sunglasses with UV400 protection. '
                        .'Reduces glare and suitable for driving and outdoor activities.',
                    'price' => 2000.00,
                    'brand' => 'Bolon',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'color' => 'Black',
                    'frame_size' => 'Large (56mm)',
                    'material' => 'Acetate',
                    'lens_type' => 'Polarized',
                    'cost_per_unit' => 750.00,
                    'ar_model_url' => 'https://models.eyecare.test/sunglasses/polarized-uv.glb',
                ],
            ],

            // 4. Accessory: color only for this category
            [
                'product' => [
                    'category_id' => $accessories->id,
                    'name' => 'Premium Microfiber Cleaning Cloth',
                    'description' => 'Ultra-soft microfiber cloth for streak-free lens cleaning. '
                        .'Safe for all coatings including anti-reflective.',
                    'price' => 50.00,
                    'brand' => 'Eyecare Essentials',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'color' => 'Gray',
                    'cost_per_unit' => 15.00,
                ],
            ],
        ];

        $productService = app(ProductService::class);

        foreach ($products as $row) {
            $productPayload = $row['product'];
            $listPrice = Arr::pull($productPayload, 'price');
            $product = Product::query()->updateOrCreate(
                ['name' => $productPayload['name']],
                $productPayload,
            );
            $variant = $productService->ensureDefaultVariantIfMissing($product);
            $variantPayload = array_merge(
                ['price' => $listPrice],
                $row['default_variant'] ?? [],
            );
            if (isset($variantPayload['duration']) && ! in_array($variantPayload['duration'], self::DURATION_OPTIONS, true)) {
                $variantPayload['duration'] = 'Monthly';
            }
            $productService->updateVariant($variant, $variantPayload);
        }
    }
}
