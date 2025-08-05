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
        Schema::create('pelurusan_reports', function (Blueprint $table) {
            $table->id(); // id_pelurusan
            $table->integer('id_harian')->default(0);
            $table->string('pelurusan_code')->nullable();
            $table->string('number_incident')->nullable();
            $table->text('incident_fallout_description')->nullable();
            $table->foreignId('tipe_order_id')->constrained('order_types');
            $table->string('order_id');
            $table->string('nomer_layanan');
            $table->string('sn_ont');
            $table->string('datek_odp');
            $table->integer('port_odp');
            $table->foreignId('fallout_status_id')->nullable()->constrained('fallout_statuses');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('notified_unassigned_at')->nullable();
            $table->timestamp('notified_uncompleted_at')->nullable();
            $table->string('keterangan')->nullable();
            $table->text('resolution_notes')->nullable(); // new column for hd_daman notes
            $table->foreignId('reporter_user_id')->nullable()->constrained('users');
            $table->bigInteger('reporter_telegram_id')->nullable();
            $table->string('reporter_telegram_username')->nullable();
            $table->timestamps();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelurusan_reports');
    }
};
