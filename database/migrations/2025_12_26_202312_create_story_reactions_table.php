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
        Schema::create('story_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('story_cards')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('ip_address')->nullable();
            $table->enum('reaction_type', ['hot', 'not', 'hmm'])->comment('🔥 ❄️ 🤔');
            $table->timestamps();

            $table->unique(['card_id', 'user_id']);
            $table->index('card_id');
            $table->index('reaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_reactions');
    }
};
