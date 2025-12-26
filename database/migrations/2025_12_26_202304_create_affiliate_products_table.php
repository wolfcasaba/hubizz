<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained('affiliate_networks')->onDelete('cascade');
            $table->string('external_id')->nullable()->comment('Product ID from network');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('image_url')->nullable();
            $table->text('affiliate_url');
            $table->json('metadata')->nullable()->comment('Brand, category, etc.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['network_id', 'external_id']);
            $table->index('is_active');
            $table->fulltext(['name', 'description']); // For product matching
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_products');
    }
};
