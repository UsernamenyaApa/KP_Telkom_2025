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
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->after('fallout_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fallout_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to_user_id');
        });
    }
};