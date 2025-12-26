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
        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('affiliate_products')->onDelete('cascade');
            $table->string('short_code')->unique()->comment('For link cloaking');
            $table->text('original_url');
            $table->text('cloaked_url')->comment('/go/xyz123');
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 10, 2)->default(0.00);
            $table->json('utm_parameters')->nullable();
            $table->timestamps();

            $table->index('post_id');
            $table->index('product_id');
            $table->index('short_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_links');
    }
};
