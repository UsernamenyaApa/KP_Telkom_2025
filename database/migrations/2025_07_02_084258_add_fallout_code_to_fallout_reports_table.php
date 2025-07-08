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
        Schema::table('fallout_reports', function (Blueprint $table) {
            $table->string('incident_ticket')->after('id_harian')->nullable();
            $table->text('incident_fallout_description')->after('incident_ticket')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fallout_reports', function (Blueprint $table) {
            $table->dropColumn('incident_ticket');
            $table->dropColumn('incident_fallout_description');
        });
    }
};