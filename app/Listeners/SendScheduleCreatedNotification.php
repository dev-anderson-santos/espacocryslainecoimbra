<?php

namespace App\Listeners;

use App\Events\ScheduleCreated;
use App\Models\SettingsModel;
use App\Notifications\SchedulesSummaryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendScheduleCreatedNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ScheduleCreated  $event
     * @return void
     */
    public function handle(ScheduleCreated $event)
    {
        $settings = SettingsModel::first();
        if (!$settings || !$settings->email_notificacao) {
            return;
        }

        $schedule = $event->schedule;

        if ($schedule->creator && $schedule->creator->is_admin) {
            return;
        }

        Notification::route('mail', $settings->email_notificacao)
            ->notify((new SchedulesSummaryNotification())
            ->delay(now()->addMinutes(5)));
    }
}
