Schema::create('queues', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('clinic_id')->nullable();
    $table->unsignedBigInteger('counter_id')->nullable(); // 🔑 WAJIB ADA
    $table->unsignedBigInteger('room_id')->nullable();    // 🔑 WAJIB ADA

    $table->string('queue_number')->nullable();
    $table->integer('queue_seq')->nullable();

    $table->boolean('notification_flags')->default(false);
    $table->timestamp('consultation_time')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->timestamp('auto_cancelled_at')->nullable();

    $table->string('status')->default('waiting');
    $table->timestamps();
});


