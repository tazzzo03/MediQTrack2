<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');                 // PK
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('info');       // contoh: queue, pharmacy, system, success, warning, error
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // FK ke patients(id) — pastikan table & PK betul (default: patients.id)
            $table->foreign('patient_id')
                  ->references('id')->on('patients')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
