<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_queue', function (Blueprint $table) {
            $table->string('failure_category', 32)->nullable()->after('queue_status');
        });

        Schema::table('scan_histories', function (Blueprint $table) {
            $table->string('failure_category', 32)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('scan_queue', function (Blueprint $table) {
            $table->dropColumn('failure_category');
        });

        Schema::table('scan_histories', function (Blueprint $table) {
            $table->dropColumn('failure_category');
        });
    }
};
