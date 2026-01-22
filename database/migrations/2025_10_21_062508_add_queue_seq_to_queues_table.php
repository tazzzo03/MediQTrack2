<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // tambah integer untuk urutan numeric
            $table->unsignedInteger('queue_seq')->nullable()->after('queue_number')->index();
        });

        // BACKFILL: isi queue_seq untuk data lama
        // andaian format queue_number seperti "A001", "A105" (huruf di depan)
        DB::statement("
            UPDATE queues
            SET queue_seq = CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)
            WHERE queue_number IS NOT NULL AND queue_seq IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('queue_seq');
        });
    }
};
