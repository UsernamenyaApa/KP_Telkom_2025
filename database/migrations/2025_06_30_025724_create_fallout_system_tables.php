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
        Schema::create('order_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('fallout_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('fallout_reports', function (Blueprint $table) {
        $table->id(); // id_fallout
        $table->foreignId('tipe_order_id')->constrained('order_types');
        $table->string('order_id'); 
        $table->string('nomer_layanan'); 
        $table->string('sn_ont'); 
        $table->string('datek_odp');
        $table->integer('port_odp'); 
        $table->foreignId('fallout_status_id')->nullable()->constrained('fallout_statuses');
        $table->string('keterangan')->nullable();
        $table->foreignId('reporter_user_id')->nullable()->constrained('users');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fallout_reports');
        Schema::dropIfExists('fallout_statuses');
        Schema::dropIfExists('order_types');
        Schema::dropIfExists('hd_damans');
    }
};