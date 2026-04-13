<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('requires_prescription')->default(false)->after('requires_expiry_tracking');
            $table->enum('stock_unit', ['units', 'pairs', 'boxes'])->default('units')->after('requires_prescription');
            $table->boolean('is_system')->default(false)->after('stock_unit');

            // Variant field flags
            $table->boolean('has_frame_size')->default(false)->after('is_system');
            $table->boolean('has_color')->default(false)->after('has_frame_size');
            $table->boolean('has_material')->default(false)->after('has_color');
            $table->boolean('has_lens_type')->default(false)->after('has_material');
            $table->boolean('has_power_field')->default(false)->after('has_lens_type');
            $table->boolean('has_duration')->default(false)->after('has_power_field');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn([
                'requires_prescription',
                'stock_unit',
                'is_system',
                'has_frame_size',
                'has_color',
                'has_material',
                'has_lens_type',
                'has_power_field',
                'has_duration',
            ]);
        });
    }
};
