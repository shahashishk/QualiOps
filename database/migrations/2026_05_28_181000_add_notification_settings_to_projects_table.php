<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('notify_pm')->default(false)->after('active');
            $table->boolean('notify_tl')->default(false)->after('notify_pm');
            $table->boolean('notify_qa')->default(false)->after('notify_tl');
            $table->boolean('notify_developer')->default(false)->after('notify_qa');
            $table->text('notification_custom_emails')->nullable()->after('notify_developer');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'notify_pm',
                'notify_tl',
                'notify_qa',
                'notify_developer',
                'notification_custom_emails',
            ]);
        });
    }
};
