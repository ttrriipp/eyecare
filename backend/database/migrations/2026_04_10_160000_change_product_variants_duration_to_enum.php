<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allowed duration values for contact-lens style variants.
     *
     * @var array<int, string>
     */
    private array $allowed = ['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Normalize legacy/free-text values to the closest supported enum.
        DB::table('product_variants')->whereIn('duration', ['Biweekly', 'biweekly'])->update(['duration' => 'Bi-weekly']);
        DB::table('product_variants')->whereIn('duration', ['daily', 'DAILY'])->update(['duration' => 'Daily']);
        DB::table('product_variants')->whereIn('duration', ['monthly', 'MONTHLY'])->update(['duration' => 'Monthly']);
        DB::table('product_variants')->whereIn('duration', ['quarterly', 'QUARTERLY'])->update(['duration' => 'Quarterly']);
        DB::table('product_variants')->whereIn('duration', ['yearly', 'YEARLY', 'Annual'])->update(['duration' => 'Yearly']);
        DB::table('product_variants')
            ->whereNotNull('duration')
            ->whereNotIn('duration', $this->allowed)
            ->update(['duration' => 'Monthly']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE product_variants MODIFY duration ENUM('Daily','Bi-weekly','Monthly','Quarterly','Yearly') NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_variants MODIFY duration VARCHAR(40) NULL');
        }
    }
};
