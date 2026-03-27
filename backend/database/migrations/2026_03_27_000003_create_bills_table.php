<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable(); // FK added when scheduling module is built
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
