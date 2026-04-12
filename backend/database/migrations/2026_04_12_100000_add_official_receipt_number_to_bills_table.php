<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bills', 'official_receipt_number')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->string('official_receipt_number')->nullable()->after('invoice_number');
            });
        }

        DB::table('bills')
            ->whereIn('payment_status', ['voided', 'refunded'])
            ->update(['balance_due' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('bills', 'official_receipt_number')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn('official_receipt_number');
            });
        }
    }
};
