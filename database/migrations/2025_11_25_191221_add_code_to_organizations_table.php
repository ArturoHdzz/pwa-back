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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name');
        });

        $orgs = DB::table('organizations')->get();
        foreach ($orgs as $org) {
            DB::table('organizations')
                ->where('id', $org->id)
                ->update(['code' => strtolower(Str::random(6))]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('code', 10)->unique()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
