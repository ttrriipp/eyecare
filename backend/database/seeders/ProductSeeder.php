<?php

namespace Database\Seeders;

use App\Enums\FrameMaterial;
use App\Enums\LensType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
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
                'slug' => 'prescription-lenses',
                'name' => 'Prescription Lenses',
                'description' => 'Single vision, bifocal, and progressive lenses with various coatings.',
                'has_ar_support' => false,
                'requires_expiry_tracking' => false,
                'requires_prescription' => true,
                'stock_unit' => 'pairs',
                'is_system' => true,
                'has_frame_size' => false,
                'has_color' => false,
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

        // forceFill is used so is_system (intentionally not in $fillable) can be set by the seeder.
        foreach ($categories as $categoryData) {
            $slug = $categoryData['slug'];
            $category = ProductCategory::withoutGlobalScopes()->firstOrNew(['slug' => $slug]);
            $category->forceFill($categoryData)->save();
        }

        // ── Category references ───────────────────────────────────────────────
        $frames = ProductCategory::where('slug', 'eyeglass-frames')->first();
        $lenses = ProductCategory::where('slug', 'prescription-lenses')->first();
        $contacts = ProductCategory::where('slug', 'contact-lenses')->first();
        $sunglasses = ProductCategory::where('slug', 'sunglasses')->first();
        $accessories = ProductCategory::where('slug', 'accessories')->first();

        // ── Products ──────────────────────────────────────────────────────────
        // Each entry: 'product' → Product fillable fields
        //             'default_variant' → optional ProductVariant fields merged onto the baseline default variant
        $products = [

            // ── Eyeglass Frames ───────────────────────────────────────────────
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
                    'frame_size' => 'Medium',
                    'material' => FrameMaterial::Acetate->value,
                    'cost_per_unit' => 600.00,
                    'ar_model_url' => 'https://models.eyecare.test/frames/classic-full-rim.glb',
                ],
            ],
            [
                'product' => [
                    'category_id' => $frames->id,
                    'name' => 'Titanium Semi-Rimless Frame',
                    'description' => 'Ultra-lightweight titanium semi-rimless frame for a sleek, minimal look. '
                                     .'Hypoallergenic and corrosion-resistant.',
                    'price' => 2800.00,
                    'brand' => 'Hangten',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'color' => 'Silver',
                    'frame_size' => 'Medium',
                    'material' => FrameMaterial::Titanium->value,
                    'cost_per_unit' => 1100.00,
                    'ar_model_url' => 'https://models.eyecare.test/frames/titanium-semi-rimless.glb',
                ],
            ],
            [
                'product' => [
                    'category_id' => $frames->id,
                    'name' => 'TR90 Flexible Frame',
                    'description' => 'Super-flexible TR90 nylon frame ideal for active lifestyles and children. '
                                     .'Impact-resistant and extremely lightweight.',
                    'price' => 1200.00,
                    'brand' => 'Peculiar',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'color' => 'Blue',
                    'frame_size' => 'Small',
                    'material' => FrameMaterial::TR90->value,
                    'cost_per_unit' => 480.00,
                    'ar_model_url' => 'https://models.eyecare.test/frames/tr90-flexible.glb',
                ],
            ],

            // ── Prescription Lenses ───────────────────────────────────────────
            [
                'product' => [
                    'category_id' => $lenses->id,
                    'name' => 'Single Vision Anti-Radiation Lens',
                    'description' => 'Single vision CR-39 lens with anti-radiation and blue-light blocking coating. '
                                     .'Includes hard coat and UV400 protection.',
                    'price' => 800.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'lens_type' => LensType::SingleVision->value,
                    'cost_per_unit' => 250.00,
                ],
            ],
            [
                'product' => [
                    'category_id' => $lenses->id,
                    'name' => 'Progressive Photochromic Lens',
                    'description' => 'No-line progressive lens with photochromic (Transitions) coating. '
                                     .'Transitions from clear indoors to dark outdoors.',
                    'price' => 3500.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'lens_type' => LensType::Progressive->value,
                    'cost_per_unit' => 1400.00,
                ],
            ],
            [
                'product' => [
                    'category_id' => $lenses->id,
                    'name' => 'Bifocal Lens',
                    'description' => 'Traditional bifocal lens with visible segment line. '
                                     .'Suitable for presbyopia patients requiring both distance and near correction.',
                    'price' => 1800.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'lens_type' => LensType::Bifocal->value,
                    'cost_per_unit' => 700.00,
                ],
            ],

            // ── Contact Lenses ────────────────────────────────────────────────
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
                    'lens_type' => LensType::Daily->value,
                    'base_curve' => '8.5',
                    'diameter' => '14.2',
                    'cost_per_unit' => 480.00,
                ],
            ],
            [
                'product' => [
                    'category_id' => $contacts->id,
                    'name' => 'Monthly Hydrogel Contact Lenses',
                    'description' => 'Pack of 6 monthly disposable hydrogel contact lenses. '
                                     .'High oxygen permeability for all-day comfort.',
                    'price' => 950.00,
                    'brand' => 'Acuvue',
                    'is_active' => true,
                ],
                'default_variant' => [
                    'lens_type' => LensType::Monthly->value,
                    'base_curve' => '8.6',
                    'diameter' => '14.0',
                    'cost_per_unit' => 380.00,
                ],
            ],

            // ── Sunglasses ────────────────────────────────────────────────────
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
                    'frame_size' => 'Large',
                    'material' => FrameMaterial::Acetate->value,
                    'lens_type' => LensType::Polarized->value,
                    'cost_per_unit' => 750.00,
                    'ar_model_url' => 'https://models.eyecare.test/sunglasses/polarized-uv.glb',
                ],
            ],

            // ── Accessories ───────────────────────────────────────────────────
            [
                'product' => [
                    'category_id' => $accessories->id,
                    'name' => 'Premium Microfiber Cleaning Cloth',
                    'description' => 'Ultra-soft microfiber cloth for streak-free lens cleaning. '
                                     .'Safe for all coatings including anti-reflective.',
                    'price' => 50.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'cost_per_unit' => 15.00,
                ],
            ],
            [
                'product' => [
                    'category_id' => $accessories->id,
                    'name' => 'Hard Shell Eyeglass Case',
                    'description' => 'Durable hard shell case for eyeglass protection. '
                                     .'Includes microfiber pouch. Available in assorted colours.',
                    'price' => 250.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'cost_per_unit' => 90.00,
                ],
            ],
            [
                'product' => [
                    'category_id' => $accessories->id,
                    'name' => 'Lens Cleaning Solution 120ml',
                    'description' => 'Anti-fog and anti-static lens cleaning spray. '
                                     .'Safe for all lens coatings including photochromic.',
                    'price' => 150.00,
                    'is_active' => true,
                ],
                'default_variant' => [
                    'cost_per_unit' => 55.00,
                ],
            ],
        ];

        $productService = app(ProductService::class);

        foreach ($products as $row) {
            $product = Product::create($row['product']);
            $variant = $productService->ensureDefaultVariantIfMissing($product);
            if (! empty($row['default_variant'])) {
                $productService->updateVariant($variant, $row['default_variant']);
            }
        }
    }
}
