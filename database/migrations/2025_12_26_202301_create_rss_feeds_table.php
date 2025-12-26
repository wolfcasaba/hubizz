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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('title');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->enum('fetch_interval', ['15min', 'hourly', 'daily'])->default('hourly');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Higher = more important');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->integer('fail_count')->default(0);
            $table->json('metadata')->nullable()->comment('Store feed info');
            $table->timestamps();

            $table->index(['is_active', 'fetch_interval']);
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};
