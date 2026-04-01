<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\ScheduleModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\ScheduleNextMonthMirrorLog;
use App\Models\DataNaoFaturadaModel;
use App\Models\SchedulesNextMonthModel;
use Illuminate\Support\Str;

class MonitorScheduleMirrorCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:mirror';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Espelhar os agendamentos fixos do mês anterior para o mês atual.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $message = null;
            $messageDebug = 0;
            $schedulesNextMonthId = [];
            $mirroredSchedules = 0;
            $schedulesNextMonth = SchedulesNextMonthModel::whereMonth('date', now()->addMonth()->format('m'))
                                ->where('is_mirrored', 1) // Está para ser espelhado
                                ->get();

            if ($schedulesNextMonth->count() == 0) {
                DB::commit();
                return;
            }

            $arrLastDays = [];
            $arrDados = [];
            $jsonDados = [];
            foreach ($schedulesNextMonth as $scheduleNext) {

                $schedule_temp = ScheduleModel::where([
                    'user_id' => $scheduleNext->user_id,
                    'room_id' => $scheduleNext->room_id,
                    //'created_by' => $scheduleNext->created_by,
                    'hour_id' => $scheduleNext->hour_id,
                    'date' => $scheduleNext->date,
                    'status' => $scheduleNext->status,
                    'tipo' => $scheduleNext->tipo,
                    'data_nao_faturada_id' => $scheduleNext->data_nao_faturada_id,
                    'valor' => $scheduleNext->valor
                ]);

                if (empty($schedule_temp->first())) { // Impedir espelhamento de agendamentos já existentes
                    // ScheduleModel::create([
                    //     'user_id' => $scheduleNext->user_id,
                    //     'room_id' => $scheduleNext->room_id,
                    //     'created_by' => $scheduleNext->created_by,
                    //     'hour_id' => $scheduleNext->hour_id,
                    //     'date' => $scheduleNext->date,
                    //     'status' => $scheduleNext->status,
                    //     'tipo' => $scheduleNext->tipo,
                    //     'data_nao_faturada_id' => $scheduleNext->data_nao_faturada_id,
                    // ]);

                    ScheduleModel::withoutEvents(function() use ($scheduleNext) {

                        // $s = new ScheduleModel();
                        // $s->user_id = $scheduleNext->user_id;
                        // $s->room_id = $scheduleNext->room_id;
                        // $s->created_by = $scheduleNext->created_by;
                        // $s->hour_id = $scheduleNext->hour_id;
                        // $s->date = $scheduleNext->date;
                        // $s->status = $scheduleNext->status;
                        // $s->tipo = $scheduleNext->tipo;
                        // $s->data_nao_faturada_id = $scheduleNext->data_nao_faturada_id;
                        // $s->save();
                        ScheduleModel::create([
                            'id' => (string) Str::uuid(), 
                            'user_id' => $scheduleNext->user_id,
                            'room_id' => $scheduleNext->room_id,
                            'created_by' => $scheduleNext->created_by,
                            'hour_id' => $scheduleNext->hour_id,
                            'date' => $scheduleNext->date,
                            'status' => $scheduleNext->status,
                            'tipo' => $scheduleNext->tipo,
                            'data_nao_faturada_id' => $scheduleNext->data_nao_faturada_id,
                            'valor' => $scheduleNext->valor
                        ]);
                    });

                    $jsonDados[$scheduleNext->user_id]['schedule_date'][] = $scheduleNext->date;
                    $jsonDados[$scheduleNext->user_id]['schedule_hour_id'][] = $scheduleNext->hour_id;
                    $jsonDados[$scheduleNext->user_id]['schedule_room_id'][] = $scheduleNext->room_id;

                    if (Carbon::parse($scheduleNext->date)->addDays(7) > Carbon::parse($scheduleNext->date)) {
                        $arr = getWeekDays($scheduleNext->date);
                        
                        $newDay = Carbon::parse(last($arr))->addDays(7)->format('Y-m-d');
                        $arrLastDays[] = getWeekDaysNextMonth($newDay);
                        $arrDados[] = [
                            'user_id' => $scheduleNext->user_id,
                            'room_id' => $scheduleNext->room_id,
                            'created_by' => $scheduleNext->created_by,
                            'hour_id' => $scheduleNext->hour_id,
                            'status' => $scheduleNext->status,
                            'tipo' => $scheduleNext->tipo,
                            'is_mirrored' => 1,
                            'valor' => $scheduleNext->valor,
                        ];
                    }

                    // $scheduleNext->update(['is_mirrored' => 0]); // Foi espelhado
                    SchedulesNextMonthModel::withoutEvents(function() use ($scheduleNext) {
                        $s = SchedulesNextMonthModel::find($scheduleNext->id);
                        $s->is_mirrored = 0; // Foi espelhado
                        $s->update();
                    });

                    $mirroredSchedules++;
                } elseif (!empty($schedule_temp)) {
                    $schedulesNextMonthId[] = $scheduleNext->id;
                    $messageDebug += 1;
                } 
            }

            if (count($arrLastDays) > 0) {
                foreach ($arrLastDays as $keyExterno => $datas) {
                    foreach ($datas as $data) {

                        $dataNaoFaturada = DataNaoFaturadaModel::where('data', $data)->first();
                        if (!is_null($dataNaoFaturada)) {
                            $arrDados[$keyExterno]['data_nao_faturada_id'] = $dataNaoFaturada->id;
                        }

                        $arrDados[$keyExterno]['date'] = $data;
                        $arrDados[$keyExterno]['data_nao_faturada_id'] = NULL;
                        
                        $scheduleNext = SchedulesNextMonthModel::where([
                            'date' => $arrDados[$keyExterno]['date'],
                            'user_id' => $arrDados[$keyExterno]['user_id'],
                            'room_id' => $arrDados[$keyExterno]['room_id'],
                            'created_by' => $arrDados[$keyExterno]['created_by'],
                            'hour_id' => $arrDados[$keyExterno]['hour_id'],
                            'status' => $arrDados[$keyExterno]['status'],
                            'tipo' => $arrDados[$keyExterno]['tipo'],
                            'is_mirrored' => 1,
                            'valor' => $arrDados[$keyExterno]['valor'],
                        ])->first();

                        if (empty($scheduleNext)) {
                            // SchedulesNextMonthModel::create($arrDados[$keyExterno]);

                            SchedulesNextMonthModel::withoutEvents(function() use ($arrDados, $keyExterno) {
                                // $s = new SchedulesNextMonthModel();
                                // $s->date = $arrDados[$keyExterno]['date'];
                                // $s->user_id = $arrDados[$keyExterno]['user_id'];
                                // $s->room_id = $arrDados[$keyExterno]['room_id'];
                                // $s->created_by = $arrDados[$keyExterno]['created_by'];
                                // $s->hour_id = $arrDados[$keyExterno]['hour_id'];
                                // $s->status = $arrDados[$keyExterno]['status'];
                                // $s->tipo = $arrDados[$keyExterno]['tipo'];
                                // $s->save();
                                $payload = $arrDados[$keyExterno];
                                $payload['id'] = (string) Str::uuid();
                                SchedulesNextMonthModel::create($payload);
                            });
                        }
                        now()->subMonths();
                    }
                }

                $userIds = array_unique($schedulesNextMonth->pluck('user_id')->toArray());
                $message = $mirroredSchedules . ' agendamentos espelhados com sucesso!. user IDs: ' . implode(',', $userIds);

                ScheduleNextMonthMirrorLog::create([
                    'message' => $message,
                    'email' => 'dev.anderson.santos@gmail.com',
                    'log' => json_encode($jsonDados)
                ]);

                DB::commit();
                
                $this->info($message);

                return;
            }

            SchedulesNextMonthModel::whereIn('id', $schedulesNextMonthId)->delete();
            $message = 'Nenhum agendamento foi espelhado. ' . $messageDebug . ' agendamentos para espelhar estavam duplicados e foram removidos.';
            ScheduleNextMonthMirrorLog::create([
                'message' => $message,
                'email' => 'dev.anderson.santos@gmail.com',
            ]);

            DB::commit();
            
            $this->info($message);

        } catch (\Exception $e) {
            DB::rollback();
            $this->error($e->getMessage());
            $this->error('Ocorreu um erro ao espelhar os agendamentos.');
        }
    }
}
