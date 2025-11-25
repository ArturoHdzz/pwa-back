<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name');
        });

        $groups = DB::table('groups')->get();
        foreach ($groups as $group) {
            DB::table('groups')
                ->where('id', $group->id)
                ->update(['code' => strtoupper(Str::random(6))]);
        }

        Schema::table('groups', function (Blueprint $table) {
            $table->string('code', 10)->unique()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
