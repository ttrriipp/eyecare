<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('base_curve')->nullable()->after('lens_type');
            $table->string('diameter')->nullable()->after('base_curve');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedInteger('reorder_quantity')->default(0)->after('reorder_level');
            $table->string('batch_number')->nullable()->after('reorder_quantity');
            $table->date('expires_at')->nullable()->after('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn(['reorder_quantity', 'batch_number', 'expires_at']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['base_curve', 'diameter']);
        });
    }
};
