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
        Schema::create('story_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->enum('type', ['swipe', 'before_after', 'numbered', 'this_or_that'])->default('swipe');
            $table->json('content')->comment('Card-specific data');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('post_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_cards');
    }
};
