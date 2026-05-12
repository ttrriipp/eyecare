<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bill_refund_requests');
    }

    public function down(): void
    {
        // Intentionally irreversible: the abandoned refund-request workflow was removed.
    }
};
