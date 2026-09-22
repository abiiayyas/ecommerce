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
        Schema::create('supplier_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('supplier_warehouse_id')->index();
            $table->string('status')->default('pending')->index();
            $table->json('order_snapshot');
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['order_shop_id', 'supplier_id', 'supplier_warehouse_id'], 'supplier_dispatch_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_dispatches');
    }
};
