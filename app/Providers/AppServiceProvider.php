<?php

namespace App\Providers;

use App\Events\QualityGateFailedEvent;
use App\Events\ScanLifecycleEvent;
use App\Events\SystemWarningEvent;
use App\Models\GitConnection;
use App\Listeners\SendGovernanceNotifications;
use App\Listeners\UpdateNotificationHistoryStatus;
use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\ScanQueue;
use App\Models\SonarConnection;
use App\Models\User;
use App\Policies\GitConnectionPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\ScanHistoryPolicy;
use App\Policies\ScanQueuePolicy;
use App\Policies\SonarConnectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(GitConnection::class, GitConnectionPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ScanHistory::class, ScanHistoryPolicy::class);
        Gate::policy(ScanQueue::class, ScanQueuePolicy::class);
        Gate::policy(SonarConnection::class, SonarConnectionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function (User $user) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        Event::listen(ScanLifecycleEvent::class, SendGovernanceNotifications::class);
        Event::listen(QualityGateFailedEvent::class, SendGovernanceNotifications::class);
        Event::listen(SystemWarningEvent::class, SendGovernanceNotifications::class);
        Event::listen(NotificationSent::class, UpdateNotificationHistoryStatus::class);
        Event::listen(NotificationFailed::class, UpdateNotificationHistoryStatus::class);
    }
}
