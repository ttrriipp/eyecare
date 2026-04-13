<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('product_id');
        });

        $assigned = [];

        $rows = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->orderBy('product_variants.product_id')
            ->orderByDesc('product_variants.is_default')
            ->orderBy('product_variants.id')
            ->select(
                'product_variants.id',
                'product_variants.product_id',
                'products.sku as product_sku',
            )
            ->get();

        foreach ($rows as $row) {
            if (! isset($assigned[$row->product_id])) {
                DB::table('product_variants')->where('id', $row->id)->update(['sku' => $row->product_sku]);
                $assigned[$row->product_id] = true;
            } else {
                DB::table('product_variants')->where('id', $row->id)->update(['sku' => $this->generateUniqueVariantSku()]);
            }
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique('sku');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_variants MODIFY sku VARCHAR(255) NOT NULL');
        } else {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('sku')->nullable(false)->change();
            });
        }

        if ($driver === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropFullText('products_fulltext_search');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sku');
        });

        if ($driver === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->fullText(['name', 'description', 'brand'], 'products_fulltext_search');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('price');
        });

        DB::statement('
            UPDATE products p
            INNER JOIN product_variants pv ON pv.product_id = p.id AND pv.is_default = 1
            SET p.sku = pv.sku
        ');

        if ($driver === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropFullText('products_fulltext_search');
            });
        }

        DB::statement('ALTER TABLE products MODIFY sku VARCHAR(255) NOT NULL');

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });

        if ($driver === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->fullText(['name', 'description', 'brand'], 'products_fulltext_search');
            });
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
    }

    private function generateUniqueVariantSku(): string
    {
        do {
            $sku = 'PRD-'.Str::upper(Str::random(8));
        } while (
            DB::table('product_variants')->where('sku', $sku)->exists()
            || DB::table('products')->where('sku', $sku)->exists()
        );

        return $sku;
    }
};
