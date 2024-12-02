<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
   {
    Schema::table('sales_target_versions', function (Blueprint $table) {
        $table->dropUnique('sales_target_versions_year_id_version_number_unique');
        $table->unique(['year', 'version_number'], 'sales_target_versions_year_version_number_unique');
    });
   }

   public function down()
   {
       Schema::table('sales_target_versions', function (Blueprint $table) {
           $table->dropUnique(['year', 'version_number']);
           
           $table->unsignedBigInteger('year_id')->after('id');
           $table->unique(['year_id', 'version_number']);
           $table->foreign('year_id')->references('id')->on('sales_target_years');
       });
   }
};