<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversations', 'subject')) {
            return;
        }

        // One row per customer: merge duplicate user_id threads into the oldest id.
        $duplicateUserIds = DB::table('conversations')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $ids = DB::table('conversations')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $keepId = array_shift($ids);
            foreach ($ids as $dropId) {
                DB::table('messages')->where('conversation_id', $dropId)->update(['conversation_id' => $keepId]);
                DB::table('conversations')->where('id', $dropId)->delete();
            }
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['subject', 'status']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('conversations', 'subject')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('user_id');
            $table->enum('status', ['open', 'closed'])->default('open')->after('subject');
            $table->index('status');
        });
    }
};
