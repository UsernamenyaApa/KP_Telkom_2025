<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('fallout_statuses')
            ->where('name', 'input ulang')
            ->update(['name' => 'cancel atau input ulang']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('fallout_statuses')
            ->where('name', 'cancel atau input ulang')
            ->update(['name' => 'input ulang']);
    }
};
