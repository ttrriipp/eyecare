<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('amount');
            $table->decimal('balance_due', 10, 2)->default(0)->after('amount_paid');
        });

        DB::table('bills')
            ->where('payment_status', 'paid')
            ->update([
                'amount_paid' => DB::raw('amount'),
                'balance_due' => 0,
            ]);

        DB::table('bills')
            ->where('payment_status', 'unpaid')
            ->update([
                'amount_paid' => 0,
                'balance_due' => DB::raw('amount'),
            ]);
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'balance_due']);
        });
    }
};
