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
        Schema::create('rss_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_feed_id')->constrained('rss_feeds')->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('items_found')->default(0);
            $table->integer('items_imported')->default(0);
            $table->integer('items_skipped')->default(0)->comment('Duplicates');
            $table->text('error_message')->nullable();
            $table->json('import_log')->nullable()->comment('Detailed import info');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['rss_feed_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_imports');
    }
};
