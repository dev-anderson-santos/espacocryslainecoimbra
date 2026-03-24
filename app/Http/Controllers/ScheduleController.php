<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use App\Models\HourModel;
use App\Models\RoomModel;
use Illuminate\Http\Request;
use App\Models\ScheduleModel;
use App\Models\SettingsModel;
use Illuminate\Support\Facades\DB;
use App\Models\DataNaoFaturadaModel;
use App\Models\Historic;
use App\Models\SchedulesNextMonthModel;

class ScheduleController extends Controller
{

    public function userSchedules(Request $request, $userId = NULL)
    {
        $dados = $request->all();

        $id = !is_null($userId) ? $userId : auth()->user()->id;

        $username = User::find($id)->name;
        $titulo = !is_null($userId) && $userId != auth()->user()->id ? 'Horários Ativos - ' . $username : 'Meus horários Ativos';

        $schedules = ScheduleModel::with('hour')
                    ->join('hours', 'schedules.hour_id', '=', 'hours.id') // Ajuste os nomes das tabelas se necessário
                    ->where('schedules.user_id', $id)
                    ->whereMonth('schedules.date', '>=', Carbon::now()->format('m'))
                    ->whereYear('schedules.date', now()->year)
                    ->orderBy('schedules.date', 'ASC')
                    ->orderBy('hours.hour', 'ASC') // Ordena pelo campo "hour" da tabela hours
                    ->select('schedules.*') // Evita conflitos de colunas duplicadas
                    ->get();

        foreach ($schedules as $item) {

            $schedule_temp = ScheduleModel::where([
                'user_id' => $item->user_id,
                'room_id' => $item->room_id,
                'hour_id' => $item->hour_id,
                'date' => $item->date,
                'status' => $item->status,
                'tipo' => $item->tipo,
                'data_nao_faturada_id' => $item->data_nao_faturada_id
            ])->orderBy('id', 'desc')->get();

            if ($schedule_temp->count() > 1 && !empty($schedule_temp->first())) {
                $schedule_temp->first()->delete();
            }
        }

        $id_user = $id;

        $historic = Historic::where('user_id', $id)->orderBy('id', 'desc')->get();
        //$schedulesNext = ScheduleModel::whereMonth('date', now()->addMonth()->format('m'))->whereYear('date', now()->year)->get();

        $resetPassWord = false;
        if (session()->has('reset_password')) {
            $resetPassWord = true;
            session()->forget('reset_password');
        }
        return view('schedule.my-schedules', compact('schedules', 'titulo', 'id_user', 'username', 'historic', 'resetPassWord'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $dataSelect = [];
        $day = NULL;
        for ($i=0; $i < config('app.days_to_show'); $i++) {
            if ($i == 0) {
                $day = Carbon::now();
                
            } else {
                $day = Carbon::now()->addDays($i);
            }

            if ($day->isSunday()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        $mostrarAgendamento = false;
        if (now()->isBetween(Carbon::parse('07:00')->format('H:i'), Carbon::parse('22:00')->format('H:i'))) {
            $mostrarAgendamento = true;
        }

        return view('schedule.index', compact('hours', 'rooms', 'dataSelect', 'mostrarAgendamento'));
    }

    public function showSpecificShcedule(Request $request)
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $_day = Carbon::parse($request->day)->format('Y-m-d');
        $schedules = ScheduleModel::where('date', $_day)->get();

        $dataSelect = [];
        $day = NULL;
        for ($i=0; $i < config('app.days_to_show'); $i++) {
            if ($i == 0) {
                $day = Carbon::now();
                
            } else {
                $day = Carbon::now()->addDays($i);
            }

            if ($day->isSunday()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        $showSpecificShedule = true;

        $mostrarAgendamento = false;
        if (now()->isBetween(Carbon::parse('07:00')->format('H:i'), Carbon::parse('22:00')->format('H:i'))) {
            $mostrarAgendamento = true;
        }

        return view('schedule.index', compact('hours', 'rooms', 'dataSelect', 'showSpecificShedule', 'schedules', '_day', 'mostrarAgendamento'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $dados = $request->all();

        try {
            DB::beginTransaction();

            $dia = $dados['date'];

            $dados['status'] = 'Ativo';

            $settings = SettingsModel::first();
            $datasNaoFaturadas = DataNaoFaturadaModel::all();
            foreach($datasNaoFaturadas as $data) {
                if (Carbon::parse($data->data)->format('Y-m-d') == Carbon::parse($dia)->format('Y-m-d')) {
                    $dados['data_nao_faturada_id'] = $data->id;
                }
            }
            
            $scheduleInUse = ScheduleModel::where([
                'date' => $dados['date'],
                'hour_id' => $dados['hour_id'],
                'room_id' => $dados['room_id'],
            ])->first();

            if($scheduleInUse) {
                return response()->json(['status' => 'warning', 'message' => 'Este horário já está ocupado.']);
            }

            $now = Carbon::now()->format('Y-m-d');
            if (Carbon::parse($now)->diffInDays($dia, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse(Carbon::parse($dia)->format('Y-m-d') . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
                $dados['status'] = 'Finalizado';
                $dados['finalizado_em'] = now()->format('Y-m-d H:i:s');
            } 

            if ($dados['tipo'] == 'Fixo') {                

                $arrDays = getWeekDays($dia);

                $newDay = Carbon::parse(end($arrDays))->addDays(7)->format('Y-m-d');
                $arrDaysNextMonth = getWeekDaysNextMonth($newDay);

                $arrDataEmUso = [];
                $horariosEmUso = false;
                $dados['valor'] = $settings->valor_fixo;
                foreach ($arrDays as $key => $value) {
                    
                    $dados['date'] = $value;

                    $scheduleInUse = ScheduleModel::where([
                        'date' => $dados['date'],
                        'hour_id' => $dados['hour_id'],
                        'room_id' => $dados['room_id'],
                        // 'user_id' => $dados['user_id']
                    ])->first();

                    if($scheduleInUse) {
                        $arrDataEmUso[$key]['data'] = Carbon::parse($value)->isoFormat('dddd, DD \d\e MMMM \d\e Y');
                        $arrDataEmUso[$key]['hora'] = HourModel::where('id', $dados['hour_id'])->first()->hour;
                        $horariosEmUso = true;
                        continue;
                    }

                    $dataNaoFaturada = $datasNaoFaturadas->where('data', $value)->first();

                    if (!is_null($dataNaoFaturada)) {
                        $dados['data_nao_faturada_id'] = $dataNaoFaturada->id;
                    }

                    ScheduleModel::create($dados);
                    $dados['status'] = 'Ativo';
                    $dados['finalizado_em'] = NULL;
                    $dados['data_nao_faturada_id'] = NULL;
                }

                foreach ($arrDaysNextMonth as $key => $value) {
                    
                    $dados['date'] = $value;

                    $dataNaoFaturada = $datasNaoFaturadas->where('data', $value)->first();
                    if (!is_null($dataNaoFaturada)) {
                        $dados['data_nao_faturada_id'] = $dataNaoFaturada->id;
                    }

                    $scheduleInUse = ScheduleModel::where([
                        'date' => $dados['date'],
                        'hour_id' => $dados['hour_id'],
                        'room_id' => $dados['room_id'],
                        // 'user_id' => $dados['user_id']
                    ])->first();

                    if($scheduleInUse) {
                        $arrDataEmUso[$key]['data'] = Carbon::parse($value)->isoFormat('dddd, DD \d\e MMMM \d\e Y');
                        $arrDataEmUso[$key]['hora'] = HourModel::where('id', $dados['hour_id'])->first()->hour;
                        $horariosEmUso = true;
                        continue;
                    }

                    $dados['is_mirrored'] = 1;
                    SchedulesNextMonthModel::create($dados);
                    $dados['data_nao_faturada_id'] = NULL;
                }

                DB::commit();
                return response()->json(['status' => 'well-done', 'horariosEmUso' => $horariosEmUso, 'arrDataEmUso' => $arrDataEmUso, 'message' => 'Agendamento realizado com sucesso!']);

            } else {
                $dados['valor'] = $settings->valor_avulso;
                ScheduleModel::create($dados);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamento realizado com sucesso!']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $dados = $request->all();
        try {
            DB::beginTransaction();
            $schedule = ScheduleModel::findOrFail($dados['schedule_id']);
    
            $now = Carbon::now()->format('Y-m-d');

            $dateFormated = Carbon::parse($schedule->date)->format('Y-m-d');

            if (auth()->user()->is_admin != 1) {
                if (Carbon::parse($now)->diffInDays($schedule->date, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse($dateFormated . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
                    return response()->json(['status' => 'info', 'message' => 'Este agendamento não pode ser cancelado.']);
                }
            }

            // dd($schedule->date, Carbon::parse($now)->diffInDays($schedule->date, false) <= 1, Carbon::parse('00:00')->format('g:i A') > Carbon::parse(SettingsModel::first()->hora_fechamento)->format('g:i A'));

            $schedule->delete();
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao cancelar o agendamento.']);
        }

    }

    public function modalSchedule(Request $request)
    {
        $dados = $request->all();

        $hour = HourModel::find($dados['hour_id']);
        $room = RoomModel::find($dados['room_id']);

        if ($room == null) {
            $message = 'Esta sala não está disponível';
            return view('schedule.modals.modal-schedule-info', compact('message'));
        }

        $inUse = false;
        $novoAgendamento = false;
        $cancelamento = false;
        $action = 'schedule.destroy';
        $data = $dados['data'];
        $now = Carbon::now()->format('Y-m-d');
        $canCancel = true;

        $schedules = ScheduleModel::where([
            'date' => $dados['data'],
            'hour_id' => $dados['hour_id'],
            'room_id' => $dados['room_id'],
            'user_id' => $dados['user_id'],
        ])->first();
        
        $schedulesB = ScheduleModel::where([
            'date' => $dados['data'],
            'hour_id' => $dados['hour_id'],
            'room_id' => $dados['room_id'],
        ])->first();
        
        // TODO: Verificar o melhor uso desta funcionalidade
        // $schedulesNextMonthReserved = SchedulesNextMonthModel::where([
        //     'date' => $dados['data'],
        //     'hour_id' => $dados['hour_id'],
        //     'room_id' => $dados['room_id'],
        // ])->first();

        // if ($schedulesNextMonthReserved) {
        //     $message = 'Este horário está reservado para espelhamento para o profissional: ' . User::find($schedulesNextMonthReserved->user_id)->name;
        //     return view('schedule.modals.modal-schedule-info', compact('message'));
        // }

        // Verifica se a sala já está em uso pela pessoa logada
        // No horário selecionado
        if (!is_null($schedules)) {
            // Se tiver, retorna o modal de cancelamento
            $inUse = true;
            $cancelamento = true;
            $novoAgendamento = false;

            if ($schedules->status == 'Finalizado') {
                $canCancel = false;
            }

            if (Carbon::parse($now)->diffInDays($schedules->date, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse(Carbon::parse($schedules->date)->format('Y-m-d') . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
                $schedules->update([
                    'status' => 'Finalizado',
                    'finalizado_em' => now()->format('Y-m-d H:i:s')
                ]);

                $canCancel = false;
            } 

            if(auth()->user()->is_admin == 1) $canCancel = true;

            // if (Carbon::parse($now)->diffInDays($schedules->date, false) == 1 && now()->format('H:i') >= SettingsModel::first()->hora_fechamento) {
            //     if ($schedules->status != 'Finalizado') {
            //         $schedules->update([
            //             'status' => 'Finalizado',
            //         ]);
            //     }

            //     $canCancel = false;
            // }
            
            return view('schedule.modals.modal-schedule', compact('schedules', 'hour', 'room', 'inUse', 'data', 'cancelamento', 'novoAgendamento', 'action', 'canCancel'));
            
        } else if ($schedules == NULL && !empty($schedulesB)) {
            // Se a sala está em uso por outra pessoa (schedulesB), 
            // retorna a mensagem informando que o horário está ocupado
            $novoAgendamento = false; 
            $cancelamento = false;
            if(auth()->user()->is_admin == 1) {
                $cancelamento = true;
                $action = 'schedule.destroy';

                if ($schedulesB->status == 'Finalizado') {
                    $canCancel = true;
                }
    
                if (Carbon::parse($now)->diffInDays($schedulesB->date, false) == 1 && now()->format('H:i') >= SettingsModel::first()->hora_fechamento) {
                    if ($schedulesB->status != 'Finalizado') {
                        $schedulesB->update([
                            'status' => 'Finalizado',
                        ]);
                    }
    
                    $canCancel = true;
                }

                $schedules = $schedulesB;
            }            

            $inUse = true;           
            return view('schedule.modals.modal-schedule', compact('hour', 'room', 'data', 'inUse', 'novoAgendamento', 'cancelamento', 'action', 'canCancel', 'schedules'));
        } else if (empty($schedules) && empty($schedulesB)) {
            // Se a sala não está em uso, retorna o modal de cadastro
            $inUse = false;
            $novoAgendamento = true;
            $cancelamento = false;
            return view('schedule.modals.modal-schedule', compact('inUse', 'novoAgendamento', 'cancelamento', 'hour', 'room', 'data'));
        }
    }

    public function fechamentosDoMes(Request $request, $user_id = null)
    {
        $id = !is_null($user_id) ? $user_id : auth()->user()->id;

        $user_name = !is_null($user_id) && $user_id != auth()->user()->id ? ' - ' . User::find($id)->name : '';

        $setting = SettingsModel::first();
        $valorFixo = $setting->valor_fixo;
        $valorAvulso = $setting->valor_avulso;

        $concluidosParcialAtivoFixo = ScheduleModel::where([
                        'user_id' => $id,
                        'status' => 'Ativo',
                        'tipo' => 'Fixo'
                    ])
                    ->where('faturado', 0)
                    ->whereMonth('date', Carbon::now()->format('m'))
                    ->whereYear('date', Carbon::now()->format('Y'))
                    ->whereNull('data_nao_faturada_id')
                    ->get();

        $concluidosParcialAtivoAvulso = ScheduleModel::where([
                        'user_id' => $id,
                        'status' => 'Ativo',
                        'tipo' => 'Avulso'
                    ])
                    ->where('faturado', 0)
                    ->whereMonth('date', Carbon::now()->format('m'))
                    ->whereYear('date', Carbon::now()->format('Y'))
                    ->whereNull('data_nao_faturada_id')
                    ->get();

        $concluidosParcialFinalizadoFixo = ScheduleModel::where([
                        'user_id' => $id,
                        'status' => 'Finalizado',
                        'tipo' => 'Fixo'
                    ])
                    ->where('faturado', 0)
                    ->whereMonth('date', Carbon::now()->format('m'))
                    ->whereYear('date', Carbon::now()->format('Y'))
                    ->whereNull('data_nao_faturada_id')
                    ->get();

        $concluidosParcialFinalizadoAvulso = ScheduleModel::where([
                        'user_id' => $id,
                        'status' => 'Finalizado',
                        'tipo' => 'Avulso'
                    ])
                    ->where('faturado', 0)
                    ->whereMonth('date', Carbon::now()->format('m'))
                    ->whereYear('date', Carbon::now()->format('Y'))
                    ->whereNull('data_nao_faturada_id')
                    ->get();

        $concluidosParcialAgendamentos = $concluidosParcialAtivoFixo->count() + $concluidosParcialAtivoAvulso->count() + $concluidosParcialFinalizadoFixo->count() + $concluidosParcialFinalizadoAvulso->count();

        // $totalParcialAgendamentos = ScheduleModel::where([
        //                 'user_id' => $id,                        
        //                 'status' => 'Ativo',
        //             ])
        //             ->whereMonth('date', Carbon::now()->format('m'))
        //             ->get();
        
        // é preciso calcular com o valor fixo e o valor avulso
        // Veirificar se algums horario foi escolhido como avulso
        // $totalAvulso = 0;
        // if ($concluidosParcialAtivoAvulso->count() > 0) {
        //     $totalAvulso = $concluidosParcialAtivoAvulso->count() * $valorAvulso;
        // }
        // if ($concluidosParcialFinalizadoAvulso->count() > 0) {
        //     $totalAvulso += $concluidosParcialFinalizadoAvulso->count() * $valorAvulso;
        // }

        // $totalFixo = 0;
        // if ($concluidosParcialAtivoFixo->count() > 0) {
        //     $totalFixo = $concluidosParcialAtivoFixo->count() * $valorFixo;
        // }
        // if ($concluidosParcialFinalizadoFixo->count() > 0) {
        //     $totalFixo += $concluidosParcialFinalizadoFixo->count() * $valorFixo;
        // }

        $totalParcialValor = $concluidosParcialAtivoFixo->sum('valor') + $concluidosParcialAtivoAvulso->sum('valor') + $concluidosParcialFinalizadoFixo->sum('valor') + $concluidosParcialFinalizadoAvulso->sum('valor');;
        // $totalParcialValor = $totalAvulso + $totalFixo;

        //---- Mês anterior

        $concluidosMesAnteriorAvulso = ScheduleModel::where([
            'user_id' => $id,
            'status' => 'Finalizado',
            'tipo' => 'Avulso',
            'faturado' => 1
        ])
        ->whereIn('tipo', ['Fixo', 'Avulso'])
        ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
        ->whereYear('date', Carbon::now()->firstOfMonth()->subMonths()->format('Y'))
        ->whereNull('data_nao_faturada_id')
        ->whereNotNull('finalizado_em')
        ->get();

        $concluidosMesAnteriorFixo = ScheduleModel::where([
                    'user_id' => $id,
                    'status' => 'Finalizado',
                    'tipo' => 'Fixo',
                    'faturado' => 1
                ])
                ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
                ->whereYear('date', Carbon::now()->firstOfMonth()->subMonths()->format('Y'))
                ->whereNull('data_nao_faturada_id')
                ->whereNotNull('finalizado_em')
                ->get();

        $concluidosAgendamentosMesAnterior = $concluidosMesAnteriorAvulso->count() + $concluidosMesAnteriorFixo->count();

        // é preciso calcular com o valor fixo e o valor avulso
        // Veirificar se algums horario foi escolhido como avulso
        // $totalAvulsoMesAnterior = 0;
        // if ($concluidosMesAnteriorAvulso->count() > 0) {
        // $totalAvulsoMesAnterior = $concluidosMesAnteriorAvulso->count() * $valorAvulso;
        // }

        // $totalFixoMesAnterior = 0;
        // if ($concluidosMesAnteriorFixo->count() > 0) {
        // $totalFixoMesAnterior = $concluidosMesAnteriorFixo->count() * $valorFixo;
        // }

        $totalMesAnterior = $concluidosMesAnteriorAvulso->sum('valor') + $concluidosMesAnteriorFixo->sum('valor');
        // $totalMesAnterior = $totalAvulsoMesAnterior + $totalFixoMesAnterior;

        return view('fechamentos-mes.index', 
            compact(
                'id',
                'user_name', 
                'concluidosParcialAgendamentos', 
                'totalParcialValor', 
                'concluidosAgendamentosMesAnterior', 
                'totalMesAnterior',
                'setting'
            ));
    }

    public function mudarTipoAgendamento(Request $request)
    {
        $dados = $request->all();

        try {
            DB::beginTransaction();
            $schedule = ScheduleModel::findOrFail($dados['schedule_id']);
            $tipo = $schedule->tipo == 'Fixo' ? 'Avulso' : 'Fixo';            
            $dia = $schedule->date;

            $datasNaoFaturadas = DataNaoFaturadaModel::all();
            foreach($datasNaoFaturadas as $data) {
                if (Carbon::parse($data->data)->format('Y-m-d') == Carbon::parse($dia)->format('Y-m-d')) {
                    $dados['data_nao_faturada_id'] = $data->id;
                }
            }

            // $now = Carbon::now()->format('Y-m-d');
            // if (Carbon::parse($now)->diffInDays($dia, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse(Carbon::parse($dia)->format('Y-m-d') . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
            //     $dados['status'] = 'Finalizado';
            //     $dados['finalizado_em'] = now()->format('Y-m-d H:i:s');
            // } 

            if ($tipo == 'Fixo') {

                $dados['user_id'] = $schedule->user_id;
                $dados['created_by'] = auth()->user()->id;
                $dados['hour_id'] = $schedule->hour_id;
                $dados['room_id'] = $schedule->room_id;
                $dados['valor'] = $schedule->valor;
                $dados['tipo'] = $tipo;

                $arrDays = getWeekDays($dia);

                $newDay = Carbon::parse(end($arrDays))->addDays(7)->format('Y-m-d');
                $arrDaysNextMonth = getWeekDaysNextMonth($newDay);

                $arrDataEmUso = [];
                $horariosEmUso = false;
                foreach ($arrDays as $key => $value) {
                    
                    $dados['date'] = Carbon::parse($value)->format('Y-m-d');

                    $schedule_temp = ScheduleModel::where([
                        'user_id' => $schedule->user_id,
                        'room_id' => $schedule->room_id,
                        'hour_id' => $schedule->hour_id,
                        'date' => $dados['date']
                    ])->first();

                    if (!empty($schedule_temp)) {
    
                        $schedule_temp->update([
                            'tipo' => $tipo
                        ]);
                    } else {

                        $scheduleInUse = ScheduleModel::where([
                            'date' => $dados['date'],
                            'hour_id' => $dados['hour_id'],
                            'room_id' => $dados['room_id'],
                        ])->first();

                        // TODO: VErificar como deixar de exibir a data 1 como se estivesse sido escolhida por alguém
                        if($scheduleInUse) {
                            $arrDataEmUso[$key]['data'] = Carbon::parse($value)->isoFormat('dddd, DD \d\e MMMM \d\e Y');
                            $arrDataEmUso[$key]['hora'] = HourModel::where('id', $dados['hour_id'])->first()->hour;
                            $horariosEmUso = true;
                            continue;
                        }

                        $dataNaoFaturada = $datasNaoFaturadas->where('data', $value)->first();
    
                        if (!is_null($dataNaoFaturada)) {
                            $dados['data_nao_faturada_id'] = $dataNaoFaturada->id;
                        }
    
                        ScheduleModel::create($dados);
                        $dados['status'] = 'Ativo';
                        $dados['finalizado_em'] = NULL;
                        $dados['data_nao_faturada_id'] = NULL;
                    }                    
                }

                foreach ($arrDaysNextMonth as $key => $value) {
                    
                    $dados['date'] = $value;

                    $dataNaoFaturada = $datasNaoFaturadas->where('data', $value)->first();
                    if (!is_null($dataNaoFaturada)) {
                        $dados['data_nao_faturada_id'] = $dataNaoFaturada->id;
                    }

                    $schedule_temp = SchedulesNextMonthModel::where([
                        'user_id' => $schedule->user_id,
                        'room_id' => $schedule->room_id,
                        'hour_id' => $schedule->hour_id,
                        'date' => $dados['date'],
                        'valor' => $schedule->valor
                    ])->first();
                    
                    if (!empty($schedule_temp)) {
                        $schedule_temp->update([
                            'tipo' => $tipo
                        ]);
                    } else {
                        $schedule_temp_other_user = ScheduleModel::where([
                            'date' => $dados['date'],
                            'hour_id' => $dados['hour_id'],
                            'room_id' => $dados['room_id'],
                        ])->first();

                        if($schedule_temp_other_user) {
                            $arrDataEmUso[$key]['data'] = Carbon::parse($value)->isoFormat('dddd, DD \d\e MMMM \d\e Y');
                            $arrDataEmUso[$key]['hora'] = HourModel::where('id', $dados['hour_id'])->first()->hour;
                            $horariosEmUso = true;
                            continue;
                        }

                        $dados['is_mirrored'] = 1;
                        SchedulesNextMonthModel::create($dados);
                        $dados['data_nao_faturada_id'] = NULL;
                    }
                }

                DB::commit();
                return response()->json(['status' => 'well-done', 'horariosEmUso' => $horariosEmUso, 'arrDataEmUso' => $arrDataEmUso, 'message' => 'Agendamento atualizado com sucesso!']);
            }

            // Quando for atualizar para Avulso,
            // Atualiza a data escolhida para Avulso e
            // Remove as demais datas a frente
            $schedule->update([
                'tipo' => $tipo
            ]);
            
            $arrDaysCurrentSchedule = getWeekDays($schedule->date);

            foreach ($arrDaysCurrentSchedule as $date) {
                
                $dados['date'] = Carbon::parse($date)->format('Y-m-d');

                $schedule_temp = ScheduleModel::where([
                    'user_id' => $schedule->user_id,
                    'room_id' => $schedule->room_id,
                    'hour_id' => $schedule->hour_id,
                    'date' => $dados['date'],
                    'tipo' => 'Fixo'
                ])->first();
                
                if (!empty($schedule_temp)) {

                    $schedule_temp_next_month = SchedulesNextMonthModel::where([
                        'user_id' => $schedule_temp->user_id,
                        'room_id' => $schedule_temp->room_id,
                        'hour_id' => $schedule_temp->hour_id,
                        'date' => $date
                    ])->first();
    
                    // Removendo as demais datas dos próximos meses que estavam reservadas para espelhar
                    if (!empty($schedule_temp_next_month)) {
                        $schedule_temp_next_month->delete();
                    }

                    // Removendo as demais datas do mês atual  
                    if ($schedule_temp->id != $schedule->id) {
                        $schedule_temp->delete();                    
                    }                  
                }                
            }

            $newDay = Carbon::parse(end($arrDaysCurrentSchedule))->addDays(7)->format('Y-m-d');
            $arrDaysNextMonth = getWeekDaysNextMonth($newDay);

            foreach ($arrDaysNextMonth as $date) {
                $schedule_temp = ScheduleModel::where([
                    'user_id' => $schedule->user_id,
                    'room_id' => $schedule->room_id,
                    'hour_id' => $schedule->hour_id,
                    'date' => $date,
                    'tipo' => 'Fixo'
                ])->first();
                // Removendo as demais datas do dos próximos meses
                if (!empty($schedule_temp) && $schedule_temp->id != $schedule->id) {
                    $schedule_temp->delete();
                }
                
                $schedule_temp_next_month = SchedulesNextMonthModel::where([
                    'user_id' => $schedule->user_id,
                    'room_id' => $schedule->room_id,
                    'hour_id' => $schedule->hour_id,
                    'date' => $date
                ])->first();
                // Removendo as demais datas dos próximos meses que estavam reservadas para espelhar
                if (!empty($schedule_temp_next_month)) {
                    $schedule_temp_next_month->delete();
                }
            }

            $newDay = Carbon::parse(end($arrDaysNextMonth))->addDays(7)->format('Y-m-d');
            $arrDaysNextMonth = getWeekDaysNextMonth($newDay);

            foreach ($arrDaysNextMonth as $date) {
                $schedule_temp = ScheduleModel::where([
                    'user_id' => $schedule->user_id,
                    'room_id' => $schedule->room_id,
                    'hour_id' => $schedule->hour_id,
                    'date' => $date,
                    'tipo' => 'Fixo'
                ])->first();
                // Removendo as demais datas do dos próximos meses
                if (!empty($schedule_temp) && $schedule_temp->id != $schedule->id) {
                    $schedule_temp->delete();
                }
                
                $schedule_temp_next_month = SchedulesNextMonthModel::where([
                    'user_id' => $schedule->user_id,
                    'room_id' => $schedule->room_id,
                    'hour_id' => $schedule->hour_id,
                    'date' => $date
                ])->first();
                // Removendo as demais datas dos próximos meses que estavam reservadas para espelhar
                if (!empty($schedule_temp_next_month)) {
                    $schedule_temp_next_month->delete();
                }
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Tipo de agendamento alterado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao mudar o tipo de agendamento.']);
        }
    }

    public function updateAllSchedules(Request $request)
    {
        try {
            DB::beginTransaction();

            $schedules = ScheduleModel::where('status', '!=', 'Finalizado')->where('user_id', auth()->user()->id)->get();
            $now = Carbon::now()->format('Y-m-d');

            foreach ($schedules as $schedule) {
                // dump($schedule->date);
                if (Carbon::parse($now)->diffInDays($schedule->date, false) < 1 && now()->format('H:i') >= SettingsModel::first()->hora_fechamento) {
                    // dd('teste');
                    $schedule->update([
                        'status' => 'Finalizado'
                    ]);
                }        
            }
            // dd('teste');

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Horários atualizados com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao atualizar os horários.']);
        }

    }

    public function details(Request $request)
    {
        $dados = $request->all();

        $schedulesToShow = null;

        $schedulesToShow = ScheduleModel::where('user_id', $dados['user_id'])
                            ->whereNotNull('finalizado_em')->where('faturado', 1)
                            ->whereIn('status', ['Ativo', 'Finalizado'])
                            ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
                            ->whereYear('date', Carbon::now()->firstOfMonth()->subMonths()->format('Y'))
                            ->whereNull('data_nao_faturada_id')
                            ->orderBy('date', 'ASC')
                            ->orderBy('hour_id', 'ASC')
                            ->get();

        if ($dados['schedule_type'] == 'MES_ATUAL') {
            $schedulesToShow = ScheduleModel::where('user_id', $dados['user_id'])
                                ->where('faturado', 0)->whereIn('status', ['Ativo', 'Finalizado'])
                                ->whereMonth('date', Carbon::now()->format('m'))
                                ->whereYear('date', Carbon::now()->format('Y'))
                                ->whereNull('data_nao_faturada_id')
                                ->orderBy('date', 'ASC')
                                ->orderBy('hour_id', 'ASC')
                                ->get();
        }

        return view('schedule.modals.modal-detalhes-mes', compact('schedulesToShow'));
    }

    public function destroyNextMonth(Request $request)
    {
        $dados = $request->all();
        try {
            DB::beginTransaction();
            $schedule = SchedulesNextMonthModel::findOrFail($dados['schedule_id']);
    
            $now = Carbon::now()->format('Y-m-d');

            $dateFormated = Carbon::parse($schedule->date)->format('Y-m-d');

            if (auth()->user()->is_admin != 1) {
                if (Carbon::parse($now)->diffInDays($schedule->date, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse($dateFormated . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
                    return response()->json(['status' => 'info', 'message' => 'Este agendamento não pode ser cancelado.']);
                }
            }

            $schedule->delete();
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao cancelar o agendamento.']);
        }
    }
    // Atualmente esta função não está sendo usada na view my-schedules.blade.php
    public function cancelAllFixedSchedules(Request $request, $user_id = NULL)
    {
        $id = !is_null($user_id) ? $user_id : auth()->user()->id;

        try {
            DB::beginTransaction();
            ScheduleModel::where([
                'user_id' => $id,
                'status' => 'Ativo',
                'tipo' => 'Fixo'
            ])
            ->whereMonth('date', now()->format('m'))
            ->delete();
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamentos cancelados com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao cancelar os agendamentos.']);
        }
    }

    // Atualmente esta função não está sendo usada na view my-schedules.blade.php
    public function cancelAllFixedNextMonthSchedules(Request $request, $user_id = NULL)
    {
        $id = !is_null($user_id) ? $user_id : auth()->user()->id;

        try {
            DB::beginTransaction();
            SchedulesNextMonthModel::where([
                'user_id' => $id,
                'status' => 'Ativo',
                'tipo' => 'Fixo'
            ])
            ->whereMonth('date', now()->addMonth()->format('m'))
            ->delete();
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamentos cancelados com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao cancelar os agendamentos.']);
        }
    }

    public function modalCancelarAgendamentoFixo(Request $request)
    {
        $dados = $request->all();
        
        $schedule = ScheduleModel::where('id', $dados['schedule_id'])->first();
        $action = $schedule->tipo == 'Fixo' ? '/app/schedule/cancelar-agendamento-fixo' : '/app/schedule/to-destroy-schedule';
        $nextWeekDays = getWeekDays($schedule->date);

        $newDay = Carbon::parse(end($nextWeekDays))->addDays(7)->format('Y-m-d');
        $nextMonthDays = getWeekDaysNextMonth($newDay);

        $daysToBeDeletedInCurrentSchedule = array_merge($nextWeekDays, $nextMonthDays);

        if (in_array($schedule->date, $daysToBeDeletedInCurrentSchedule) && count($daysToBeDeletedInCurrentSchedule) > 1) {
            unset($daysToBeDeletedInCurrentSchedule[array_search($schedule->date, $daysToBeDeletedInCurrentSchedule)]);
        }

        $daysToBeDeletedInCurrentSchedule = array_values($daysToBeDeletedInCurrentSchedule);

        $otherWeekSchedules = NULL;
        $otherWeekSchedulesNextMonth = NULL;
        $nextMonthDays = NULL;
        if (count($daysToBeDeletedInCurrentSchedule) > 0) {
            $otherWeekSchedules = ScheduleModel::where('user_id', $schedule->user_id)
                                ->whereIn('date', $daysToBeDeletedInCurrentSchedule)->where('date', '!=', $schedule->date)
                                ->where('tipo', 'Fixo')
                                ->where('hour_id', $schedule->hour_id)
                                ->where('room_id', $schedule->room_id)
                                ->get();
            
            $newDay = Carbon::parse(end($nextWeekDays))->addDays(7)->format('Y-m-d');
            $nextMonthDays = getWeekDaysNextMonth($newDay);

            if (count($nextMonthDays) > 0) {

                $newDayAux = end($nextMonthDays);
                $newDayOfLastMonth = Carbon::parse($newDayAux)->addDays(7)->format('Y-m-d');
                $daysOfLastMonth = getWeekDaysNextMonth($newDayOfLastMonth);

                $nextMonthDays = array_merge($nextMonthDays, $daysOfLastMonth);

                $otherWeekSchedulesNextMonth = SchedulesNextMonthModel::where('user_id', $schedule->user_id)
                                                ->whereIn('date', $nextMonthDays)
                                                ->where('status', 'Ativo')
                                                ->where('tipo', 'Fixo')
                                                ->where('hour_id', $schedule->hour_id)
                                                ->where('room_id', $schedule->room_id)
                                                ->get();
            }
        }

        return view('schedule.modals.modal-cancelar-agendamento-fixo', [
            'schedule' => $schedule,
            'nextWeekDays' => $nextWeekDays,
            'otherWeekSchedules' => $otherWeekSchedules,
            'otherWeekSchedulesNextMonth' => $otherWeekSchedulesNextMonth,
            'action' => $action,
        ]);
    }

    public function cancelarAgendamentoFixo(Request $request)
    {
        $dados = $request->all();
        try {
            DB::beginTransaction();
            $schedule = ScheduleModel::findOrFail($dados['schedule_id']);
    
            $now = Carbon::now()->format('Y-m-d');
    
            $dateFormated = Carbon::parse($schedule->date)->format('Y-m-d');
    
            if (auth()->user()->is_admin != 1) {
                if (Carbon::parse($now)->diffInDays($schedule->date, false) <= 1 && now()->format('Y-m-d H:i') > Carbon::parse($dateFormated . ' ' . SettingsModel::first()->hora_fechamento)->subDays()->format('Y-m-d H:i')) {
                    return response()->json(['status' => 'info', 'message' => 'Este agendamento não pode ser cancelado.']);
                }
            }
    
            if (isset($dados['otherWeekSchedules']) && count($dados['otherWeekSchedules']) > 0) {
                $agendamentos = ScheduleModel::whereIn('id', $dados['otherWeekSchedules'])->get();
                foreach($agendamentos as $item) {
                    $item->delete();
                }
            }

            if (isset($dados['otherWeekSchedulesNextMonth']) && count($dados['otherWeekSchedulesNextMonth']) > 0) {
                $agendamentos = SchedulesNextMonthModel::whereIn('id', $dados['otherWeekSchedulesNextMonth'])->get();
                foreach($agendamentos as $item) {
                    $item->delete();
                }
            }
            
            $schedule->delete();
    
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado com sucesso!']);
    
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao cancelar o agendamento.', 'error' => $e->getMessage()]);
        }
    }

    public function indexAdmin()
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $dataSelect = [];
        $day = NULL;
        for ($i=0; $i < 60; $i++) {
            if ($i == 0) {
                $day = Carbon::now();
                
            } else {
                $day = Carbon::now()->addDays($i);
            }

            if ($day->isSunday()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        return view('schedule.index-administrador', compact('hours', 'rooms', 'dataSelect'));
    }

    public function showSpecificShceduleAdministrador(Request $request)
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $_day = Carbon::parse($request->day)->format('Y-m-d');
        $schedules = ScheduleModel::where('date', $_day)->get();

        $dataSelect = [];
        $day = NULL;
        for ($i=0; $i < 60; $i++) {
            if ($i == 0) {
                $day = Carbon::now();
                
            } else {
                $day = Carbon::now()->addDays($i);
            }

            if ($day->isSunday()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        $showSpecificShedule = true;

        return view('schedule.index-administrador', compact('hours', 'rooms', 'dataSelect', 'showSpecificShedule', 'schedules', '_day'));
    }

    public function scheduleSearch(Request $request)
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $_day = Carbon::parse($request->day)->format('Y-m-d');
        $schedules = ScheduleModel::where('date', $_day)->get();

        $dataSelect = [];
        $day = NULL;
        for ($i = 0; $i < now()->daysInMonth; $i++) {
            if ($i == 0) {
                $day = now()->startOfMonth();
                
            } else {
                $day = now()->startOfMonth()->addDays($i);
            }

            if ($day->isSunday() || $day->isNextMonth()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        return view('schedule.schedule-search', compact('hours', 'rooms', 'dataSelect', 'schedules', '_day'));
    }

    public function showSpecificShceduleMonth(Request $request)
    {
        $hours = HourModel::all();
        $rooms = RoomModel::all();

        $_day = Carbon::parse($request->date)->format('Y-m-d');
        $schedules = ScheduleModel::where('date', $_day)->get();

        $dataSelect = [];
        $day = NULL;
        for ($i = 0; $i < now()->daysInMonth; $i++) {
            if ($i == 0) {
                $day = now()->startOfMonth();
                
            } else {
                $day = now()->startOfMonth()->addDays($i);
            }

            if ($day->isSunday() || $day->isNextMonth()) {                
                continue;
            }

            array_push($dataSelect, $day);
        }

        $showSpecificShedule = true;

        return view('schedule.schedule-search', compact('hours', 'rooms', 'dataSelect', 'showSpecificShedule', 'schedules', '_day'));
    }

    private function horariosDuplicados(int $userId)
    {
        return ScheduleModel::selectRaw('user_id, hour_id, date, count(1) as total')
                            ->whereBetween('date', [Carbon::parse(now()->startOfMonth()->format('Y-m-d'))->format('Y-m-d'), Carbon::parse(now()->endOfMonth()->format('Y-m-d'))->format('Y-m-d')])
                            ->where('user_id', $userId)
                            ->orderBy('date')
                            ->groupBy('schedules.user_id', 'schedules.hour_id', 'schedules.date')
                            ->havingRaw('count(1) > 1')
                            ->get();
    }
}
