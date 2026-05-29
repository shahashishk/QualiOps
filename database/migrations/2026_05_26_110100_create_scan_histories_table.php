<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('branch_name');
            $table->string('scan_type');
            $table->foreignId('triggered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('queue_id')->nullable()->constrained('scan_queue')->nullOnDelete();
            $table->string('sonar_project_key');
            $table->string('sonar_dashboard_url')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('total_bugs')->nullable();
            $table->unsignedInteger('total_vulnerabilities')->nullable();
            $table->unsignedInteger('total_code_smells')->nullable();
            $table->decimal('coverage', 5, 2)->nullable();
            $table->decimal('duplicated_lines', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_histories');
    }
};
