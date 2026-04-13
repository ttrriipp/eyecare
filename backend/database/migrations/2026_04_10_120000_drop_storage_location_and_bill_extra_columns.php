<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['storage_location']);
            $table->dropColumn('storage_location');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'official_receipt_number',
                'tax_amount',
                'remarks',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('storage_location')->nullable()->after('expires_at');
            $table->index('storage_location');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('official_receipt_number')->nullable()->after('invoice_number');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('amount');
            $table->text('remarks')->nullable()->after('collected_by');
        });
    }
};
