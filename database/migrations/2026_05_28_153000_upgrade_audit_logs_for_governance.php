<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->after('user_id');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            $table->json('old_values')->nullable()->after('action');
            $table->json('new_values')->nullable()->after('old_values');
            $table->string('ip_address', 45)->nullable()->after('new_values');
            $table->text('user_agent')->nullable()->after('ip_address');
        });

        DB::table('audit_logs')
            ->whereNull('entity_type')
            ->update([
                'entity_type' => DB::raw('auditable_type'),
                'entity_id' => DB::raw('auditable_id'),
                'new_values' => DB::raw('metadata'),
            ]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'entity_type',
                'entity_id',
                'old_values',
                'new_values',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
