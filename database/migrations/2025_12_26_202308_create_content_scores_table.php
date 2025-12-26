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
        Schema::create('content_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->unique()->constrained('posts')->onDelete('cascade');
            $table->integer('viral_score')->default(0)->comment('0-100');
            $table->integer('views')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('reactions')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0.00)->comment('Percentage');
            $table->decimal('ctr', 5, 2)->default(0.00)->comment('Click-through rate');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('viral_score');
            $table->index('engagement_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_scores');
    }
};
