<?php

use App\Enums\FulfillmentType;
use App\Models\Supplier\SupplierOffer;
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
        Schema::table('product_flats', function (Blueprint $table) {
            $table->string('fulfillment_type')
                ->default(FulfillmentType::OwnedStock->value)
                ->index();
            $table->foreignIdFor(SupplierOffer::class, 'active_supplier_offer_id')
                ->nullable()
                ->constrained('supplier_offers')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_flats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_supplier_offer_id');
            $table->dropIndex(['fulfillment_type']);
            $table->dropColumn('fulfillment_type');
        });
    }
};
