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
   Schema::create('web_push_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('profile_id')
        ->constrained('profiles')
        ->onDelete('cascade');

    $table->string('endpoint')->unique();
    $table->string('public_key');
    $table->string('auth_token');
    $table->string('content_encoding')->nullable();
    $table->timestamps();
});

}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_push_subscriptions');
    }
};
