<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->after('counter_id');
        });
    }

    public function down()
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('room_id');
        });
    }
};
