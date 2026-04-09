<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 48);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('from_payment_status', 32)->nullable();
            $table->string('to_payment_status', 32);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['bill_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_histories');
    }
};
