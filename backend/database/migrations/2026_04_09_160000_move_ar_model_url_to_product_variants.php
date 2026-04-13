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
            $table->string('ar_model_url')->nullable()->after('is_default');
        });

        $rows = DB::table('products')->select('id', 'ar_model_url')->whereNotNull('ar_model_url')->get();

        foreach ($rows as $row) {
            DB::table('product_variants')
                ->where('product_id', $row->id)
                ->update(['ar_model_url' => $row->ar_model_url]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('ar_model_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ar_model_url')->nullable()->after('brand');
        });

        $variants = DB::table('product_variants')
            ->select('id', 'product_id', 'ar_model_url', 'is_default')
            ->whereNotNull('ar_model_url')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        foreach ($variants as $productId => $group) {
            $first = $group->first();
            DB::table('products')->where('id', $productId)->update(['ar_model_url' => $first->ar_model_url]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('ar_model_url');
        });
    }
};
