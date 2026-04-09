<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $rows = DB::table('product_images')->select('id', 'product_id')->get();

        foreach ($rows as $row) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $row->product_id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');

            if ($variantId) {
                DB::table('product_images')->where('id', $row->id)->update(['product_variant_id' => $variantId]);
            }
        }

        DB::table('product_images')->whereNull('product_variant_id')->delete();

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $rows = DB::table('product_images')->select('id', 'product_variant_id')->get();

        foreach ($rows as $row) {
            $productId = DB::table('product_variants')
                ->where('id', $row->product_variant_id)
                ->value('product_id');

            if ($productId) {
                DB::table('product_images')->where('id', $row->id)->update(['product_id' => $productId]);
            }
        }

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
