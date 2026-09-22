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
        Schema::create('marketing_event_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_event_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('external_id')->nullable();
            $table->text('response')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['marketing_event_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_event_deliveries');
    }
};
