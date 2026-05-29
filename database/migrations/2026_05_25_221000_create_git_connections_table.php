<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_connections', function (Blueprint $table) {
            $table->id();
            $table->string('connection_name')->unique();
            $table->string('provider_type');
            $table->string('base_url');
            $table->string('auth_type');
            $table->string('username')->nullable();
            $table->text('token')->nullable();
            $table->longText('ssh_private_key')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_status')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_connections');
    }
};
