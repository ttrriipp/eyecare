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
            $table->string('power')->nullable()->after('lens_type');
            $table->string('duration')->nullable()->after('power');
        });

        DB::table('product_variants')
            ->whereNull('power')
            ->whereNotNull('base_curve')
            ->update(['power' => DB::raw('base_curve')]);

        DB::table('product_variants')
            ->whereNull('duration')
            ->whereNotNull('diameter')
            ->update(['duration' => DB::raw('diameter')]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['power', 'duration']);
        });
    }
};
