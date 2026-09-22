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
        Schema::create('landing_page_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('campaign_id')->default(0)->index();
            $table->date('date');
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedBigInteger('checkouts')->default(0);
            $table->unsignedBigInteger('orders')->default(0);
            $table->unsignedBigInteger('paid_orders')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['landing_page_id', 'campaign_id', 'date'], 'landing_daily_campaign_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_daily_stats');
    }
};
