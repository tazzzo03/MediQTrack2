<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // buang column yang tak perlu
            $table->dropColumn(['latitude', 'longitude', 'radius']);

            // tambah column baru
            $table->string('name')->nullable()->after('clinic_name');
        });
    }


    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['name', 'role', 'room_id']);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->float('radius')->nullable();
        });
    }
};
