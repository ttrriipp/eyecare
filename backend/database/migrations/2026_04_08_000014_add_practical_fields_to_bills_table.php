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
            $table->string('official_receipt_number')->nullable()->after('invoice_number');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('amount');
            $table->foreignId('collected_by')
                ->nullable()
                ->after('payment_method')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('remarks')->nullable()->after('collected_by');

            $table->index('payment_method');
            $table->index('collected_by');
        });

        // Normalize pre-existing rows to avoid report fragmentation.
        DB::table('bills')->whereNotNull('payment_method')->update([
            'payment_method' => DB::raw('LOWER(TRIM(payment_method))'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['collected_by']);
            $table->dropForeign(['collected_by']);
            $table->dropColumn([
                'official_receipt_number',
                'tax_amount',
                'collected_by',
                'remarks',
            ]);
        });
    }
};
