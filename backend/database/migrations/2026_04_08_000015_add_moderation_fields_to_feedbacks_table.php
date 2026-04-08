<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->boolean('is_verified_purchase')->default(false)->after('comment');
            $table->boolean('is_visible')->default(true)->after('is_verified_purchase');
            $table->text('admin_reply')->nullable()->after('is_visible');
            $table->foreignId('moderated_by')
                ->nullable()
                ->after('admin_reply')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');

            $table->index('is_visible');
            $table->index('is_verified_purchase');
            $table->index('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropIndex(['is_visible']);
            $table->dropIndex(['is_verified_purchase']);
            $table->dropIndex(['moderated_by']);
            $table->dropForeign(['moderated_by']);
            $table->dropColumn([
                'is_verified_purchase',
                'is_visible',
                'admin_reply',
                'moderated_by',
                'moderated_at',
            ]);
        });
    }
};
