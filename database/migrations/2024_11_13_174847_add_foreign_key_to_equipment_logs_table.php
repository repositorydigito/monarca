<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Agregamos el índice y la llave foránea para equipment_id
            $table->index('equipment_id');
            $table->foreign('equipment_id')
                ->references('id')
                ->on('equipments')
                ->onDelete('cascade')
                ->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropIndex(['equipment_id']);
        });
    }
};