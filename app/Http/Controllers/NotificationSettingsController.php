<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationSettingsUpdateRequest;
use App\Services\System\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request, SystemSettingsService $systemSettingsService): View
    {
        abort_unless($request->user()->can('notifications.manage'), 403);

        return view('notifications.settings', [
            'breadcrumbs' => [
                ['label' => 'Notifications'],
                ['label' => 'Settings'],
            ],
            'settings' => $systemSettingsService->notificationSettings(),
        ]);
    }

    public function update(NotificationSettingsUpdateRequest $request, SystemSettingsService $systemSettingsService): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $systemSettingsService->set($key, (bool) $value);
        }

        foreach (array_diff(array_keys($systemSettingsService->notificationSettings()), array_keys($request->validated())) as $key) {
            $systemSettingsService->set($key, false);
        }

        return back()->with('success', 'Notification settings updated successfully.');
    }
}
