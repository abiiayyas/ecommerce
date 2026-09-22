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
        Schema::create('shipping_rate_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_flat_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('courier_company');
            $table->string('courier_service');
            $table->string('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('destination_area_id');
            $table->unsignedInteger('quantity');
            $table->string('payload_hash', 64);
            $table->json('provider_payload')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_quotes');
    }
};
