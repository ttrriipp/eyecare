<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('processed_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('ready_at')->nullable()->after('notes');
            $table->timestamp('completed_at')->nullable()->after('ready_at');

            $table->index('processed_by');
            $table->index('ready_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['processed_by']);
            $table->dropIndex(['ready_at']);
            $table->dropIndex(['completed_at']);
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['processed_by', 'ready_at', 'completed_at']);
        });
    }
};
