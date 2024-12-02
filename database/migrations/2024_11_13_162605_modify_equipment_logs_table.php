<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyEquipmentLogsTable extends Migration
{
    public function up()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Verificamos si la columna equipment_id no existe antes de crearla
            if (!Schema::hasColumn('equipment_logs', 'equipment_id')) {
                $table->foreignId('equipment_id')
                    ->after('project_id')
                    ->constrained()
                    ->onDelete('cascade')
                    ->onUpdate('no action');
            }
            
            // Verificamos si la columna equipment existe antes de eliminarla
            if (Schema::hasColumn('equipment_logs', 'equipment')) {
                $table->dropColumn('equipment');
            }
        });
    }

    public function down()
    {
        Schema::table('equipment_logs', function (Blueprint $table) {
            // Verificamos si la columna equipment no existe antes de crearla
            if (!Schema::hasColumn('equipment_logs', 'equipment')) {
                $table->string('equipment')->nullable()->comment('Equipo')->after('engine_hours');
            }
            
            // Verificamos si la columna equipment_id existe antes de eliminarla
            if (Schema::hasColumn('equipment_logs', 'equipment_id')) {
                $table->dropForeign(['equipment_id']);
                $table->dropColumn('equipment_id');
            }
        });
    }
}