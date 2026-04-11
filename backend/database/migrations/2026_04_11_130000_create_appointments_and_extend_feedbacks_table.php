<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('scheduled_at')->nullable();
                $table->string('status')->default('scheduled');
                $table->string('appointment_type')->nullable();
                $table->string('doctor_name')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasColumn('feedbacks', 'feedback_type')) {
            $this->ensureProductCompositeUniqueAndForeignKey();

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'product_id']);
            });
        } catch (\Throwable) {
        }

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('feedback_type', 32)->default('product');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->cascadeOnDelete();

            $table->string('approval_status', 32)->default('pending');
            $table->timestamp('approval_reviewed_at')->nullable();
            $table->foreignId('approval_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            $table->unique(['user_id', 'appointment_id']);
            $table->index(['feedback_type', 'approval_status']);
        });

        DB::table('feedbacks')->update([
            'feedback_type' => 'product',
            'approval_status' => 'approved',
        ]);

        $this->ensureProductCompositeUniqueAndForeignKey();

        Schema::enableForeignKeyConstraints();
    }

    private function ensureProductCompositeUniqueAndForeignKey(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->unique(['user_id', 'product_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        } catch (\Throwable) {
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('feedbacks', 'feedback_type')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'product_id']);
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'appointment_id']);
            });
        } catch (\Throwable) {
        }

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['approval_reviewed_by']);
            $table->dropForeign(['appointment_id']);
            $table->dropForeign(['product_id']);

            $table->dropColumn([
                'feedback_type',
                'appointment_id',
                'approval_status',
                'approval_reviewed_at',
                'approval_reviewed_by',
                'rejection_reason',
            ]);
        });

        DB::table('feedbacks')->whereNull('product_id')->delete();

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::enableForeignKeyConstraints();

        Schema::dropIfExists('appointments');
    }
};
