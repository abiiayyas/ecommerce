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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->index();
            $table->string('provider');
            $table->string('message_type')->index();
            $table->char('deduplication_key', 64)->unique();
            $table->char('recipient_hash', 64)->index();
            $table->text('recipient');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('external_id')->nullable();
            $table->text('response')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
