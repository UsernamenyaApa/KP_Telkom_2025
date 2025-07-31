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
            $table->unsignedBigInteger('telegram_user_id')->nullable()->after('tipe_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fallout_reports', function (Blueprint $table) {
            $table->dropColumn('telegram_user_id');
        });
    }
};
