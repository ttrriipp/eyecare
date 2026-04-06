<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the existing cascading FK and replace with restrict
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->restrictOnDelete();
        });

        // Add soft-deletes to product_categories
        Schema::table('product_categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add fulltext index for search performance (MySQL/MariaDB)
        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'description', 'brand'], 'products_fulltext_search');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText('products_fulltext_search');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->cascadeOnDelete();
        });
    }
};
