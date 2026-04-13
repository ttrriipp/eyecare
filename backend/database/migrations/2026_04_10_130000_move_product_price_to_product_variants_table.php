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
            $table->decimal('price', 10, 2)->default(0)->after('cost_per_unit');
        });

        foreach (DB::table('product_variants')->select('id', 'product_id', 'price_adjustment')->cursor() as $row) {
            $base = (float) DB::table('products')->where('id', $row->product_id)->value('price');
            $adj = (float) ($row->price_adjustment ?? 0);
            DB::table('product_variants')->where('id', $row->id)->update([
                'price' => round($base + $adj, 2),
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('price_adjustment');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('description');
        });

        foreach (DB::table('product_variants')->where('is_default', true)->select('id', 'product_id', 'price')->cursor() as $row) {
            DB::table('products')->where('id', $row->product_id)->update([
                'price' => $row->price,
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('price_adjustment', 10, 2)->default(0)->after('diameter');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
