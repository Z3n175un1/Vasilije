<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.personal', function (Blueprint $table) {
            if (!Schema::connection('pgsql')->hasColumn('global.personal', 'direccion')) {
                $table->text('direccion')->nullable()->after('licencia');
            }
            if (!Schema::connection('pgsql')->hasColumn('global.personal', 'email')) {
                $table->string('email', 100)->nullable()->after('direccion');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.personal', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'email']);
        });
    }
};
