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

        $mail = (new MailMessage)
            ->subject('📆 Novos agendamentos registrados')
            ->greeting('Olá!')
            ->line('Novos agendamentos foram registrados hoje:')
            ->line('');

        foreach ($agendamentos as $userId => $items) {
            $profissional = $items->first()->user->name ?? 'Sem nome';
            $fixos = $items->where('tipo', 'fixo');
            $avulsos = $items->where('tipo', 'avulso');

            $mail->line("👤 Profissional: **{$profissional}**");
            $mail->line("Fixos: {$fixos->count()} | Avulsos: {$avulsos->count()}");

            foreach ($fixos as $ag) {
                $mail->line("🗓️ {$ag->date->format('d/m/Y')} - Sala {$ag->room->name} - " .
                            Carbon::parse($ag->hour->hour)->format('H:i'));
            }
            foreach ($avulsos as $ag) {
                $mail->line("🗓️ {$ag->date->format('d/m/Y')} - Sala {$ag->room->name} - " .
                            Carbon::parse($ag->hour->hour)->format('H:i'));
            }

            $mail->line(''); // separa visualmente
        }

        $mail->salutation('Atenciosamente, ' . config('app.name'));

        return $mail;
    }
}
