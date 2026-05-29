<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_type');
            $table->json('filters')->nullable();
            $table->string('format');
            $table->string('file_path')->nullable();
            $table->string('status');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'report_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_histories');
    }
};
