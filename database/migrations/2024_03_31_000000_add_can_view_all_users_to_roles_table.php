<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'can_view_all_users')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('can_view_all_users')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'can_view_all_users')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('can_view_all_users');
            });
        }
    }
};
