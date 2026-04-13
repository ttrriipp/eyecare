<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 10, 2)->nullable()->after('price_adjustment');
        });

        if (Schema::hasColumn('products', 'cost_per_unit')) {
            DB::statement('
                UPDATE product_variants pv
                INNER JOIN products p ON pv.product_id = p.id
                SET pv.cost_per_unit = p.cost_per_unit
                WHERE p.cost_per_unit IS NOT NULL
            ');
        }

        if (Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('supplier_id');
            });
        }

        if (Schema::hasColumn('products', 'cost_per_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('cost_per_unit');
            });
        }

        Schema::dropIfExists('suppliers');

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->cascadeOnDelete();
        });

        DB::statement('
            UPDATE product_images pi
            INNER JOIN product_variants pv ON pi.product_variant_id = pv.id
            SET pi.product_id = pv.product_id
        ');

        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->change();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        DB::table('product_images')->whereNull('product_variant_id')->delete();

        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable(false)->change();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 10, 2)->nullable()->after('price');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
        });

        if (Schema::hasColumn('product_variants', 'cost_per_unit')) {
            DB::statement('
                UPDATE products p
                INNER JOIN product_variants pv ON pv.product_id = p.id AND pv.is_default = 1
                SET p.cost_per_unit = pv.cost_per_unit
                WHERE pv.cost_per_unit IS NOT NULL
            ');
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('cost_per_unit');
        });
    }
};
