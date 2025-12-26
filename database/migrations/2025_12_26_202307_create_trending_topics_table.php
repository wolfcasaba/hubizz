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
        Schema::create('trending_topics', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->enum('source', ['google', 'twitter', 'reddit', 'manual'])->default('google');
            $table->integer('score')->default(0)->comment('Viral potential score');
            $table->string('region', 2)->nullable()->comment('Country code');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->boolean('is_used')->default(false)->comment('Has content been created?');
            $table->foreignId('post_id')->nullable()->constrained('posts')->onDelete('set null');
            $table->json('metadata')->nullable()->comment('Additional trend data');
            $table->timestamp('trending_at');
            $table->timestamps();

            $table->index(['source', 'trending_at']);
            $table->index('is_used');
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trending_topics');
    }
};
