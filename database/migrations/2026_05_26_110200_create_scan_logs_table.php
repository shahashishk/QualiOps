<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_history_id')->constrained('scan_histories')->cascadeOnDelete();
            $table->string('log_level');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
