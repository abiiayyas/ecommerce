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
        Schema::table('order_shop_items', function (Blueprint $table) {
            $table->json('fulfillment_data')->nullable()->after('product_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_shop_items', function (Blueprint $table) {
            $table->dropColumn('fulfillment_data');
        });
    }
};
