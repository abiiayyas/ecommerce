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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('landing_page_id')->nullable()->after('location_id')->constrained()->nullOnDelete();
            $table->string('sales_channel')->default('storefront')->after('landing_page_id')->index();
            $table->string('payment_mode')->nullable()->after('sales_channel')->index();
            $table->string('fulfillment_status')->default('pending')->after('status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landing_page_id');
            $table->dropIndex(['sales_channel']);
            $table->dropIndex(['payment_mode']);
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['sales_channel', 'payment_mode', 'fulfillment_status']);
        });
    }
};
