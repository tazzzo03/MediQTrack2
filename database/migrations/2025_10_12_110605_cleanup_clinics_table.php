<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // buang column yang redundant
            if (Schema::hasColumn('clinics', 'clinic_name')) {
                $table->dropColumn('clinic_name');
            }
            if (Schema::hasColumn('clinics', 'is_approved')) {
                $table->dropColumn('is_approved');
            }
            if (Schema::hasColumn('clinics', 'license_no')) {
                $table->dropColumn('license_no');
            }
            if (Schema::hasColumn('clinics', 'license_file')) {
                $table->dropColumn('license_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('clinic_name')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->string('license_no')->nullable();
            $table->string('license_file')->nullable();
        });
    }
};

