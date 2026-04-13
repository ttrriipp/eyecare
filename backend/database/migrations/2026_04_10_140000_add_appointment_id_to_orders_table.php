<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable()->after('user_id');
            $table->index('appointment_id');
        });

        if (Schema::hasTable('appointments')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('appointment_id')
                    ->references('id')
                    ->on('appointments')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasTable('appointments')) {
                $table->dropForeign(['appointment_id']);
            }
            $table->dropIndex(['appointment_id']);
            $table->dropColumn('appointment_id');
        });
    }
};
