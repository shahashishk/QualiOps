<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_schedule_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('eligible_projects_count')->default(0);
            $table->unsignedInteger('queued_projects_count')->default(0);
            $table->unsignedInteger('skipped_projects_count')->default(0);
            $table->string('status', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_schedule_logs');
    }
};
