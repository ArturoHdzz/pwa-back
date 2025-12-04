<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('chat_messages', function (Blueprint $table) {
        $table->text('attachment_path')->nullable()->after('body');
        $table->text('body')->nullable()->change(); // body deja de ser NOT NULL
    });
}

public function down()
{
    Schema::table('chat_messages', function (Blueprint $table) {
        $table->dropColumn('attachment_path');
        $table->text('body')->nullable(false)->change();
    });
}

};
