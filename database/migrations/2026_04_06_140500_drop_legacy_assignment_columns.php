<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('route_assignments', 'assignee_name')) {
            Schema::table('route_assignments', function (Blueprint $table) {
                $table->dropColumn('assignee_name');
            });
        }

        if (Schema::hasColumn('route_assignments', 'token')) {
            Schema::table('route_assignments', function (Blueprint $table) {
                $table->dropColumn('token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('route_assignments', 'assignee_name')) {
                $table->string('assignee_name', 120)->nullable();
            }

            if (!Schema::hasColumn('route_assignments', 'token')) {
                $table->string('token', 64)->nullable();
            }
        });
    }
};
