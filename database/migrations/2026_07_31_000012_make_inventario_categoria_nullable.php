<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE global.inventario ALTER COLUMN categoria DROP NOT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE global.inventario ALTER COLUMN categoria SET NOT NULL');
    }
};
