<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('has_ar_support')->default(false)->after('description');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('color')->nullable();
            $table->string('frame_size')->nullable();
            $table->string('material')->nullable();
            $table->string('lens_type')->nullable();
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('product_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        foreach (DB::table('products')->orderBy('id')->cursor() as $product) {
            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'color' => null,
                'frame_size' => null,
                'material' => $product->frame_material ?? null,
                'lens_type' => $product->lens_type ?? null,
                'price_adjustment' => 0,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('id')->constrained('product_variants')->cascadeOnDelete();
        });

        foreach (DB::table('inventory')->orderBy('id')->cursor() as $row) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $row->product_id)
                ->where('is_default', true)
                ->value('id');

            if ($variantId) {
                DB::table('inventory')->where('id', $row->id)->update(['product_variant_id' => $variantId]);
            }
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique('inventory_product_id_unique');
            $table->dropColumn('product_id');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable(false)->change();
            $table->unique('product_variant_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('order_id')->constrained('product_variants')->restrictOnDelete();
        });

        foreach (DB::table('order_items')->orderBy('id')->cursor() as $row) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $row->product_id)
                ->where('is_default', true)
                ->value('id');

            if ($variantId) {
                DB::table('order_items')->where('id', $row->id)->update(['product_variant_id' => $variantId]);
            }
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable(false)->change();
            $table->index('product_variant_id');
        });

        Schema::table('products', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropFullText('products_fulltext_search');
            }
            $table->dropColumn(['lens_type', 'frame_material']);
            if ($driver === 'mysql') {
                $table->fullText(['name', 'description', 'brand'], 'products_fulltext_search');
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('products', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropFullText('products_fulltext_search');
            }
            $table->string('lens_type')->nullable()->after('brand');
            $table->string('frame_material')->nullable()->after('lens_type');
            if ($driver === 'mysql') {
                $table->fullText(['name', 'description', 'brand'], 'products_fulltext_search');
            }
        });

        foreach (DB::table('product_variants')->where('is_default', true)->orderBy('id')->cursor() as $variant) {
            DB::table('products')->where('id', $variant->product_id)->update([
                'lens_type' => $variant->lens_type,
                'frame_material' => $variant->material,
            ]);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained('products')->restrictOnDelete();
        });

        foreach (DB::table('order_items')->orderBy('id')->cursor() as $row) {
            $productId = DB::table('product_variants')->where('id', $row->product_variant_id)->value('product_id');
            if ($productId) {
                DB::table('order_items')->where('id', $row->id)->update(['product_id' => $productId]);
            }
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->index('product_id');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->cascadeOnDelete();
        });

        foreach (DB::table('inventory')->orderBy('id')->cursor() as $row) {
            $productId = DB::table('product_variants')->where('id', $row->product_variant_id)->value('product_id');
            if ($productId) {
                DB::table('inventory')->where('id', $row->id)->update(['product_id' => $productId]);
            }
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropUnique(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->unique('product_id');
        });

        Schema::dropIfExists('product_variants');

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('has_ar_support');
        });
    }
};
