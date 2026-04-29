<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('warehouses', function (Blueprint $table) {
        $table->string('warehouse_code')->nullable()->change();
    });
}

public function down()
{
    Schema::table('warehouses', function (Blueprint $table) {
        $table->string('warehouse_code')->nullable(false)->change();
    });
}
};
