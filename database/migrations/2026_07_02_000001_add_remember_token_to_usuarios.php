<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.usuarios', function (Blueprint $table) {
            $table->rememberToken()->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.usuarios', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
