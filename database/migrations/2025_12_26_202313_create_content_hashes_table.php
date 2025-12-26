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
        Schema::create('content_hashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->unique()->constrained('posts')->onDelete('cascade');
            $table->string('title_hash', 64)->comment('SHA-256 hash');
            $table->string('content_hash', 64)->comment('SHA-256 hash');
            $table->timestamps();

            $table->index('title_hash');
            $table->index('content_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_hashes');
    }
};
