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
            $table->timestamp('taken_at')->nullable()->after('updated_at');
            $table->timestamp('completed_at')->nullable()->after('taken_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fallout_reports', function (Blueprint $table) {
            $table->dropColumn(['taken_at', 'completed_at']);
        });
    }
};