<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (!Schema::hasColumn('queues', 'supabase_log_id')) {
                $table->string('supabase_log_id')->nullable()->after('room_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'supabase_log_id')) {
                $table->dropColumn('supabase_log_id');
            }
        });
    }
};
