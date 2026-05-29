<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->foreignId('git_connection_id')->constrained('git_connections')->restrictOnDelete();
            $table->string('git_url');
            $table->string('branch_name');
            $table->foreignId('sonar_connection_id')->constrained('sonar_connections')->restrictOnDelete();
            $table->string('sonar_project_key')->unique();
            $table->string('sonar_project_name');
            $table->string('scan_type');
            $table->boolean('schedule_enabled')->default(false);
            $table->string('schedule_frequency')->default('manual');
            $table->time('schedule_time')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
