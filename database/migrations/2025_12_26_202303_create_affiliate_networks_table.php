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
        Schema::create('affiliate_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Amazon, AliExpress, eBay');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('tracking_id')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00)->comment('Percentage');
            $table->json('config')->nullable()->comment('Network-specific settings');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_networks');
    }
};
