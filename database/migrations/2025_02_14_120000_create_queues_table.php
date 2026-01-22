<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('counter_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();

            $table->string('queue_number')->nullable();
            $table->integer('queue_seq')->nullable();

            $table->boolean('notification_flags')->default(false);
            $table->timestamp('consultation_time')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('auto_cancelled_at')->nullable();

            $table->string('status')->default('waiting');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};



