<?php

namespace App\Observers;

use App\Events\ScheduleCreated;
use App\Models\Historic;
use App\Models\ScheduleModel;

class ScheduleObserver
{
    /**
     * Handle the schedule model "created" event.
     *
     * @param  \App\Models\ScheduleModel  $scheduleModel
     * @return void
     */
    public function created(ScheduleModel $scheduleModel)
    {
        Historic::create([
            'action' => 'create',
            'user_id' => $scheduleModel->user_id,
            'room_id' => $scheduleModel->room_id,
            'created_by' => auth()->user()->id,
            'hour_id' => $scheduleModel->hour_id,
            'date' => $scheduleModel->date,
            'status' => $scheduleModel->status,
            'tipo' => $scheduleModel->tipo,
            'faturado' => $scheduleModel->faturado,
            'finalizado_em' => $scheduleModel->finalizado_em,
            'data_nao_faturada_id' => $scheduleModel->data_mnao_faturada_id,
            'last_login_time' => now(),
            'last_login_ip' => request()->getClientIp(),
        ]);

        event(new ScheduleCreated($scheduleModel));
    }

    /**
     * Handle the schedule model "updated" event.
     *
     * @param  \App\Models\ScheduleModel  $scheduleModel
     * @return void
     */
    public function updated(ScheduleModel $scheduleModel)
    {
        Historic::create([
            'action' => 'update',
            'user_id' => $scheduleModel->user_id,
            'room_id' => $scheduleModel->room_id,
            'created_by' => auth()->user()->id, //$scheduleModel->created_by,
            'hour_id' => $scheduleModel->hour_id,
            'date' => $scheduleModel->date,
            'status' => $scheduleModel->status,
            'tipo' => $scheduleModel->tipo,
            'faturado' => $scheduleModel->faturado,
            'finalizado_em' => $scheduleModel->finalizado_em,
            'data_nao_faturada_id' => $scheduleModel->data_mnao_faturada_id,
            'last_login_time' => now(),
            'last_login_ip' => request()->getClientIp(),
        ]);
    }

    /**
     * Handle the schedule model "deleted" event.
     *
     * @param  \App\Models\ScheduleModel  $scheduleModel
     * @return void
     */
    public function deleted(ScheduleModel $scheduleModel)
    {
        Historic::create([
            'action' => 'delete',
            'user_id' => $scheduleModel->user_id,
            'room_id' => $scheduleModel->room_id,
            'created_by' => auth()->user()->id,
            'hour_id' => $scheduleModel->hour_id,
            'date' => $scheduleModel->date,
            'status' => $scheduleModel->status,
            'tipo' => $scheduleModel->tipo,
            'faturado' => $scheduleModel->faturado,
            'finalizado_em' => $scheduleModel->finalizado_em,
            'data_nao_faturada_id' => $scheduleModel->data_mnao_faturada_id,
            'last_login_time' => now(),
            'last_login_ip' => request()->getClientIp(),
            'deleted_by' => auth()->user()->id
        ]);
    }

    /**
     * Handle the schedule model "restored" event.
     *
     * @param  \App\Models\ScheduleModel  $scheduleModel
     * @return void
     */
    public function restored(ScheduleModel $scheduleModel)
    {
        //
    }

    /**
     * Handle the schedule model "force deleted" event.
     *
     * @param  \App\Models\ScheduleModel  $scheduleModel
     * @return void
     */
    public function forceDeleted(ScheduleModel $scheduleModel)
    {
        //
    }
}
