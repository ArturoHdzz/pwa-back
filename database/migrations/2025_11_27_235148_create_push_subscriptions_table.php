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
       // database/migrations/xxxx_xx_xx_create_push_subscriptions_table.php
Schema::create('push_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->uuid('profile_id'); // o user_id, como prefieras
    $table->string('endpoint')->unique();
    $table->text('public_key');
    $table->text('auth_token');
    $table->string('content_encoding');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
