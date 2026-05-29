<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('scan_history_id')->nullable()->constrained('scan_histories')->nullOnDelete();
            $table->string('event_type');
            $table->string('recipient');
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_histories');
    }
};
