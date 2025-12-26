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
        Schema::create('daily_izz', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->json('post_ids')->comment('Array of top 5 post IDs');
            $table->text('summary')->nullable()->comment('AI-generated summary');
            $table->integer('total_views')->default(0);
            $table->integer('total_shares')->default(0);
            $table->timestamp('curated_at')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_izz');
    }
};
