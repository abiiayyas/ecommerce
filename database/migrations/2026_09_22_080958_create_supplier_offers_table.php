<?php

use App\Models\Product\ProductFlat;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierWarehouse;
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
        Schema::create('supplier_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Supplier::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(SupplierWarehouse::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(ProductFlat::class)->constrained()->cascadeOnDelete();
            $table->string('supplier_sku');
            $table->decimal('cost_price', 15, 2);
            $table->unsignedBigInteger('available_stock')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['supplier_id', 'supplier_warehouse_id', 'supplier_sku'],
                'supplier_offers_supplier_warehouse_sku_unique',
            );
            $table->index(['product_flat_id', 'is_active', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_offers');
    }
};
