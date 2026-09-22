<?php

use App\Models\Supplier\Supplier;
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
        Schema::create('supplier_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Supplier::class)->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->text('address');
            $table->string('postal_code', 16)->nullable();
            $table->string('area_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('primary_shipping_provider')->default('biteship');
            $table->string('fallback_shipping_provider')->nullable();
            $table->json('provider_area_ids')->nullable();
            $table->json('provider_address_ids')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['supplier_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_warehouses');
    }
};
