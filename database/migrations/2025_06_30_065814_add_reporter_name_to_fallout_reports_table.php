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
            // Drop the old string column if it exists
            if (Schema::hasColumn('fallout_reports', 'reporter_name')) {
                $table->dropColumn('reporter_name');
            }
        });

        Schema::table('fallout_reports', function (Blueprint $table) {
            // Add the new foreign key column
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->after('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fallout_reports', function (Blueprint $table) {
            // Drop the foreign key column
            $table->dropConstrainedForeignId('reporter_user_id');
        });

        Schema::table('fallout_reports', function (Blueprint $table) {
            // Re-add the old string column (for rollback purposes)
            $table->string('reporter_name')->nullable()->after('order_id');
        });
    }
};