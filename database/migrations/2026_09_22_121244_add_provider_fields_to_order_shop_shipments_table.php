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
        Schema::table('order_shop_shipments', function (Blueprint $table): void {
            $table->string('provider')->default('biteship')->after('order_shop_id');
            $table->string('external_id')->nullable()->after('provider');
            $table->json('provider_payload')->nullable()->after('status');
            $table->json('status_history')->nullable()->after('provider_payload');
            $table->text('last_error')->nullable()->after('status_history');
            $table->timestamp('booked_at')->nullable()->after('last_error');
            $table->timestamp('tracked_at')->nullable()->after('booked_at');
            $table->index(['provider', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_shop_shipments', function (Blueprint $table): void {
            $table->dropIndex(['provider', 'external_id']);
            $table->dropColumn([
                'provider', 'external_id', 'provider_payload', 'status_history',
                'last_error', 'booked_at', 'tracked_at',
            ]);
        });
    }
};
