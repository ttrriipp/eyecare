<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_payment_histories', function (Blueprint $table) {
            $table->foreignId('authorized_by_user_id')
                ->nullable()
                ->after('actor_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billing_payment_histories', function (Blueprint $table) {
            $table->dropForeign(['authorized_by_user_id']);
            $table->dropColumn('authorized_by_user_id');
        });
    }
};
