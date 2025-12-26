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
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['article', 'headline', 'meta', 'tags', 'rewrite'])->default('article');
            $table->text('input')->comment('Original prompt/content');
            $table->longText('output')->comment('Generated content');
            $table->string('model')->default('perplexity')->comment('AI model used');
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 8, 4)->default(0.0000)->comment('USD');
            $table->foreignId('post_id')->nullable()->constrained('posts')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
