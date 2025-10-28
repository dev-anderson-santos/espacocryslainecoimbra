<?php

namespace App\Notifications;

use App\Models\ScheduleModel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SchedulesSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $agendamentos = ScheduleModel::whereDate('created_at', today())
            ->with(['user', 'room', 'hour'])
            ->get()
            ->groupBy('user_id');

        $msg = "Novos agendamentos foram registrados:\n\n";

        foreach ($agendamentos as $userId => $items) {

            $profissional = $items->first()->user->name ?? 'Sem nome';

            $fixos = $items->where('tipo', 'fixo');
            $avulsos = $items->where('tipo', 'avulso');

            $msg .= "Profissional {$profissional}\n";
            $msg .= "Agendamentos Fixos: " . $fixos->count() . "\n";

            foreach ($fixos as $ag) {
                $msg .= $ag->date->format('d/m/Y') . " | Sala " . $ag->room->name . " | " . Carbon::parse($ag->hour->hour)->format('H:i') . "\n";
            }

            $msg .= "Agendamentos Avulsos: " . $avulsos->count() . "\n";

            foreach ($avulsos as $ag) {
                $msg .= $ag->date->format('d/m/Y') . " | Sala " . $ag->room->name . " | " . Carbon::parse($ag->hour->hour)->format('H:i') . "\n";
            }

            $msg .= "\n";
        }

        return (new MailMessage)
            ->subject('📆 Novos agendamentos registrados')
            ->greeting('Olá!')
            ->line('Novos agendamentos foram registrados hoje:')
            ->line('')
            ->line(new \Illuminate\Support\HtmlString(nl2br(e($msg))))
            ->salutation('Atenciosamente, ' . config('app.name'));
    }
}
