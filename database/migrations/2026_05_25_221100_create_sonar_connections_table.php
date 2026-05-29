<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sonar_connections', function (Blueprint $table) {
            $table->id();
            $table->string('connection_name')->unique();
            $table->string('sonar_url');
            $table->text('token');
            $table->boolean('active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_status')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sonar_connections');
    }
};
