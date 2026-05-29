<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sonar_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('sonar_project_key');
            $table->unsignedInteger('bugs')->nullable();
            $table->unsignedInteger('vulnerabilities')->nullable();
            $table->unsignedInteger('code_smells')->nullable();
            $table->unsignedInteger('security_hotspots')->nullable();
            $table->decimal('coverage', 5, 2)->nullable();
            $table->decimal('duplicated_lines_density', 5, 2)->nullable();
            $table->string('reliability_rating', 2)->nullable();
            $table->string('security_rating', 2)->nullable();
            $table->string('maintainability_rating', 2)->nullable();
            $table->unsignedInteger('technical_debt')->nullable();
            $table->string('quality_gate_status', 32)->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->index(['project_id', 'synced_at']);
            $table->index(['sonar_project_key', 'synced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sonar_metric_snapshots');
    }
};
