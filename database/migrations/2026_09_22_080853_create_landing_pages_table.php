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
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('headline');
            $table->string('subheadline')->nullable();
            $table->json('content')->nullable();
            $table->string('cta_text')->default('Pesan sekarang');
            $table->string('accent_color', 20)->default('#ca4a2c');
            $table->boolean('online_payment_enabled')->default(true);
            $table->boolean('cod_enabled')->default(false);
            $table->string('meta_pixel_id')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
