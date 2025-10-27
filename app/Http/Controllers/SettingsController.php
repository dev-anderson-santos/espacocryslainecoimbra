<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
// use App\Models\BillingModel;
use Illuminate\Http\Request;
use App\Models\ScheduleModel;
use App\Models\SettingsModel;
use App\Models\SettingsModelLog;
use Illuminate\Support\Facades\DB;
use App\Models\DataNaoFaturadaModel;
use App\Models\RoomModel;
use App\Models\SchedulesNextMonthModel;
use App\User;
use Illuminate\Support\Arr;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $setting = SettingsModel::first();
        $datas_nao_faturadas = DataNaoFaturadaModel::orderBy('data', 'desc')->get();
        $rooms = RoomModel::all();
        $duplicatedSchedules = Arr::flatten($this->duplicatedSchedules());

        return view('settings.index', compact('setting', 'datas_nao_faturadas', 'rooms', 'duplicatedSchedules'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $dados = $request->all();
        
        try {
            DB::beginTransaction();

            $settings = SettingsModel::find($dados['id']);
            $settings->update([
                'valor_fixo' => $dados['valor_fixo'],
                'valor_avulso' => $dados['valor_avulso'],
                'hora_fechamento' => $dados['hora_fechamento'],
                'dia_fechamento' => $dados['dia_fechamento'],
                'email_notificacao' => $dados['email_notificacao']
            ]);

            $this->updateSchedulesPrice($settings);

            SettingsModelLog::create([
                'user_id' => auth()->user()->id,
                'settings_id' => $settings->id,
                'valor_fixo' => $settings->valor_fixo,
                'valor_avulso' => $settings->valor_avulso,
                'email_notificacao' => $settings->email_notificacao,
            ]);

            DB::commit();
            return redirect()->route('settings.index')->with(['success' => true, 'message' => 'Configurações atualizadas com sucesso!']);
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->route('settings.index')->with(['error' => true, 'message' => 'Ocorreu um erro ao atualizar as configurações!']);
        }
    }

    public function updateSettingsAjax(Request $request)
    {
        try {
            DB::beginTransaction();

            $settings = SettingsModel::find($request->settingsID);
            $settings->update([
                'valor_fixo' => $request->valor_fixo,
                'valor_avulso' => $request->valor_avulso,
                'hora_fechamento' => $request->hora_fechamento,
                'dia_fechamento' => $request->dia_fechamento,
            ]);

            $this->updateSchedulesPrice($settings);

            SettingsModelLog::create([
                'user_id' => auth()->user()->id,
                'settings_id' => $settings->id,
                'valor_fixo' => $settings->valor_fixo,
                'valor_avulso' => $settings->valor_avulso,
                'hora_fechamento' => $settings->hora_fechamento,
                'dia_fechamento' => $settings->dia_fechamento,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'settings_id' => $settings->id,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['error' => true, 'message' => 'Ocorreu um erro ao atualizar as configurações!']);
        }
    }

    public function modalAdicionarDataNaoFaturada(Request $request)
    {        
        return view('settings.modals.modal-adicionar-data-nao-faturada', ['settings_id' => SettingsModel::find($request->settings_id)]);
    }

    public function adicionarDataNaoFaturada(Request $request)
    {
        $dados = $request->all();
        
        try {
            DB::beginTransaction();

            $dataNaoFaturada = DataNaoFaturadaModel::create(['data' => $dados['data']]);
            $schedules = ScheduleModel::all();
            $schedulesNextMonth = SchedulesNextMonthModel::all();

            foreach($schedules as $schedule) {
                if (Carbon::parse($schedule->date)->format('Y-m-d') == Carbon::parse($dataNaoFaturada->data)->format('Y-m-d')) {
                    $schedule->update([
                        'data_nao_faturada_id' => $dataNaoFaturada->id,
                    ]);
                }
            }

            foreach($schedulesNextMonth as $schedule) {
                if (Carbon::parse($schedule->date)->format('Y-m-d') == Carbon::parse($dataNaoFaturada->data)->format('Y-m-d')) {
                    $schedule->update([
                        'data_nao_faturada_id' => $dataNaoFaturada->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Data adicionada com sucesso!']);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao adicionar a data não faturada!', 'messageDebug' => $e->getMessage()]);
        }
    }

    public function removerDataNaoFaturada(Request $request)
    {
        try {
            DB::beginTransaction();

            $dataNaoFaturada = DataNaoFaturadaModel::find($request->data_nao_faturada_id);
            $schedules = ScheduleModel::withTrashed()->get();
            $schedulesNextMonth = SchedulesNextMonthModel::all();

            foreach($schedules as $schedule) {
                if ($schedule->data_nao_faturada_id == $dataNaoFaturada->id) {
                    $schedule->update([
                        'data_nao_faturada_id' => null,
                    ]);
                }
            }

            foreach($schedulesNextMonth as $schedule) {
                if ($schedule->data_nao_faturada_id == $dataNaoFaturada->id) {
                    $schedule->update([
                        'data_nao_faturada_id' => null,
                    ]);
                }
            }

            $dataNaoFaturada->delete();

            DB::commit();

            return response()->json(['type' => 'success', 'message' => 'Data removida com sucesso!']);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['type' => 'error', 'message' => 'Ocorreu um erro ao remover a data!', 'msgDebug' => $e->getMessage()]);
        }
    }

    public function faturar()
    {
        try {
            DB::beginTransaction();

            $schedules = ScheduleModel::where('status', 'Finalizado')
                ->where('faturado', '!=', 1)
                ->whereBetween('date', [
                    now()->subMonth()->startOfMonth()->format('Y-m-d'),
                    now()->subMonth()->endOfMonth()->format('Y-m-d')
                ])->get();

            foreach ($schedules as $schedule) {
                $schedule->update([
                    'faturado' => 1
                ]);      
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Agendamentos faturados com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao faturar os agendamentos!']);
        }
    }

    public function mirror()
    {
        try {
            DB::beginTransaction();

            $schedulesNextMonth = SchedulesNextMonthModel::whereMonth('date', now()->addMonth()->format('m'))->get();

            if ($schedulesNextMonth->count() == 0) {
                return response()->json(['status' => 'info', 'message' => 'Não há agendamentos para espelhar para o mês seguinte.']);
            }

            $arrLastDays = [];
            $arrDados = [];
            foreach ($schedulesNextMonth as $scheduleNext) {
                if ($scheduleNext->is_mirrored != 1) {
                    
                    $schedule_temp = ScheduleModel::where([
                        'user_id' => $scheduleNext->user_id,
                        'room_id' => $scheduleNext->room_id,
                        'created_by' => $scheduleNext->created_by,
                        'hour_id' => $scheduleNext->hour_id,
                        'date' => $scheduleNext->date,
                        'status' => $scheduleNext->status,
                        'tipo' => $scheduleNext->tipo,
                        'data_nao_faturada_id' => $scheduleNext->data_nao_faturada_id
                    ])->first();

                    if (empty($schedule_temp)) {
                        ScheduleModel::create([
                            'user_id' => $scheduleNext->user_id,
                            'room_id' => $scheduleNext->room_id,
                            'created_by' => $scheduleNext->created_by,
                            'hour_id' => $scheduleNext->hour_id,
                            'date' => $scheduleNext->date,
                            'status' => $scheduleNext->status,
                            'tipo' => $scheduleNext->tipo,
                            'data_nao_faturada_id' => $scheduleNext->data_nao_faturada_id,
                        ]);

                        if (Carbon::parse($scheduleNext->date)->addDays(7)->format('m') > Carbon::parse($scheduleNext->date)->format('m')) {
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
                            ];
                        }
                    }
                }
            }

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
                    ])->first();

                    if (empty($scheduleNext)) {
                        SchedulesNextMonthModel::create($arrDados[$keyExterno]);
                    }                    
                }
            }

            DB::commit();
            
            return response()->json(['status' => 'success', 'message' => 'Agendamentos espalhados com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao espalhar os agendamentos!', 'erro' => $e->getMessage()]);
        }
    }

    public function deleteMirroredSchedules()
    {
        try {
            DB::beginTransaction();
            
            SchedulesNextMonthModel::whereMonth('date', now()->addMonth()->format('m'))->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Agendamentos excluídos com sucesso!']);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao excluir os agendamentos!']);
        }
    }

    public function deleteDuplicatedSchedules()
    {
        try {
            DB::beginTransaction();

            $duplicatedSchedules = 0;
            $usersId = User::all()->pluck('id')->toArray();

            $schedules = ScheduleModel::whereIn('user_id', $usersId)
            ->whereMonth('date', '>=', Carbon::now()->format('m'))
            ->whereYear('date', now()->year)
            ->orderBy('date', 'ASC')
            ->orderBy('hour_id', 'ASC')
            ->get();

            foreach ($schedules as $item) {

                $scheduleTemp = ScheduleModel::where([
                    'user_id' => $item->user_id,
                    'room_id' => $item->room_id,
                    'hour_id' => $item->hour_id,
                    'date' => $item->date,
                    'status' => $item->status,
                    'tipo' => $item->tipo,
                    'data_nao_faturada_id' => $item->data_nao_faturada_id
                ])->orderBy('id', 'desc')->get();

                if ($scheduleTemp->count() > 1 && !empty($scheduleTemp->first())) {
                    $scheduleTemp->first()->delete();
                    $duplicatedSchedules++;
                }
            }

            $message = $duplicatedSchedules == 0 ? 'Não há agendamento duplicado para excluir.' : $duplicatedSchedules . ' agendamentos duplicados foram excluídos com sucesso!';

            DB::commit();

            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao excluir os agendamentos duplicados!', 'messageDebug' => $th->getMessage()]);
        }
        
    }

    public function updateSchedulesPriceManually(Request $request)
    {
        try {
            DB::beginTransaction();

            $countFixo = 0;
            $countAvulso = 0;

            $schedules = ScheduleModel::all();
        
            foreach($schedules as $item) {
                if ($item->tipo == 'Fixo') {
                    $countFixo++;

                    $item->update([
                        'valor' => $request->valorFixo
                    ]);
                }
                if ($item->tipo == 'Avulso') {
                    $countAvulso++;

                    $item->update([
                        'valor' => $request->valorAvulso
                    ]);
                }
            }

            $schedulesNextMonth = SchedulesNextMonthModel::all();

            foreach($schedulesNextMonth as $item) {
                if ($item->tipo == 'Fixo') {
                    $item->update([
                        'valor' => $request->valorFixo
                    ]);
                }
                if ($item->tipo == 'Avulso') {
                    $item->update([
                        'valor' => $request->valorAvulso
                    ]);
                }
            }            

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Valores atualizados com sucesso. Fixos: ' . $countFixo . ' Avulsos: ' . $countAvulso]);
        } catch (\Exception $th) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao atualizar os valores!', 'messageDebug' => $th->getMessage()]);
        }
    }

    private function updateSchedulesPrice(SettingsModel $settings)
    {
        $schedules = ScheduleModel::whereMonth('date', '>=', Carbon::now()->firstOfMonth()->format('m'))
                        ->whereYear('date', '>=', Carbon::now()->firstOfMonth()->format('Y'))
                        ->get();

        foreach($schedules as $item) {
            if ($item->tipo == 'Fixo') {
                $item->update([
                    'valor' => $settings->valor_fixo
                ]);
            }
            if ($item->tipo == 'Avulso') {
                $item->update([
                    'valor' => $settings->valor_avulso
                ]);
            }
        }

        $schedulesNextMonth = SchedulesNextMonthModel::whereMonth('date', '>=', Carbon::now()->firstOfMonth()->format('m'))
                                ->whereYear('date', '>=', Carbon::now()->firstOfMonth()->format('Y'))
                                ->get();

        foreach($schedulesNextMonth as $item) {
            if ($item->tipo == 'Fixo') {
                $item->update([
                    'valor' => $settings->valor_fixo
                ]);
            }
            if ($item->tipo == 'Avulso') {
                $item->update([
                    'valor' => $settings->valor_avulso
                ]);
            }
        }
    }

    private function duplicatedSchedules()
    {
        $duplicatedSchedules = [];
        $usersId = User::all()->pluck('id')->toArray();

        $schedules = ScheduleModel::whereIn('user_id', $usersId)
        ->whereMonth('date', '>=', Carbon::now()->format('m'))
        ->whereYear('date', now()->year)
        ->orderBy('date', 'ASC')
        ->orderBy('hour_id', 'ASC')
        ->get();

        foreach ($schedules as $item) {

            $scheduleTemp = ScheduleModel::where([
                'room_id' => $item->room_id,
                'hour_id' => $item->hour_id,
                'date' => $item->date,
                'data_nao_faturada_id' => $item->data_nao_faturada_id
            ])->orderBy('id', 'desc')->first();
            
            // if ($scheduleTemp->count() > 1 && !empty($scheduleTemp->first())) {
            if ($scheduleTemp->room_id == $item->room_id
                && $scheduleTemp->hour_id == $item->hour_id
                && $scheduleTemp->date == $item->date
                && $scheduleTemp->data_nao_faturada_id == $item->data_nao_faturada_id
                && $scheduleTemp->user_id != $item->user_id
            ) {

                $duplicatedSchedules[] = $scheduleTemp;
                $duplicatedSchedules[] = $item;
            }
        }

        return $duplicatedSchedules;
    }

    public function syncDates(Request $request)
    {        
        try {
            DB::beginTransaction();

            $datasNaoFaturadas = DataNaoFaturadaModel::all();
            $schedules = ScheduleModel::whereNull('data_nao_faturada_id')->get();
            $schedulesNextMonth = SchedulesNextMonthModel::whereNull('data_nao_faturada_id')->get();

            foreach($schedules as $schedule) {
                foreach($datasNaoFaturadas as $datasNaoFaturada) {
                    if (Carbon::parse($schedule->date)->format('Y-m-d') == Carbon::parse($datasNaoFaturada->data)->format('Y-m-d')) {
                        $schedule->update([
                            'data_nao_faturada_id' => $datasNaoFaturada->id,
                        ]);
                    }
                }
            }

            foreach($schedulesNextMonth as $schedule) {
                foreach($datasNaoFaturadas as $datasNaoFaturada) {
                    if (Carbon::parse($schedule->date)->format('Y-m-d') == Carbon::parse($datasNaoFaturada->data)->format('Y-m-d')) {
                        $schedule->update([
                            'data_nao_faturada_id' => $datasNaoFaturada->id,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Datas sincronizadas com sucesso!']);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro ao sincronizar as datas não faturadas.', 'messageDebug' => $e->getMessage()]);
        }
    }

    // public function generateInvoicing()
    // {
    //     $setting = SettingsModel::first();
    //     $valorFixo = $setting->valor_fixo;
    //     $valorAvulso = $setting->valor_avulso;

    //     //---- Mês anterior ----//
    //     $concluidosMesAnteriorAvulso = ScheduleModel::where([
    //         'status' => 'Finalizado',
    //         'tipo' => 'Avulso',
    //         'faturado' => 1
    //     ])
    //     ->whereIn('tipo', ['Fixo', 'Avulso'])
    //     ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
    //     ->whereNull('data_nao_faturada_id')
    //     ->get();

    //     $concluidosMesAnteriorFixo = ScheduleModel::where([
    //                 'status' => 'Finalizado',
    //                 'tipo' => 'Fixo',
    //                 'faturado' => 1
    //             ])
    //             ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
    //             ->whereNull('data_nao_faturada_id')
    //             ->get();

    //     $concluidosAgendamentosMesAnterior = $concluidosMesAnteriorAvulso->count() + $concluidosMesAnteriorFixo->count();

    //     // é preciso calcular com o valor fixo e o valor avulso
    //     // Veirificar se algums horario foi escolhido como avulso
    //     $totalAvulsoMesAnterior = 0;
    //     if ($concluidosMesAnteriorAvulso->count() > 0) {
    //     $totalAvulsoMesAnterior = $concluidosMesAnteriorAvulso->count() * $valorAvulso;
    //     }

    //     $totalFixoMesAnterior = 0;
    //     if ($concluidosMesAnteriorFixo->count() > 0) {
    //     $totalFixoMesAnterior = $concluidosMesAnteriorFixo->count() * $valorFixo;
    //     }

    //     $totalMesAnterior = $totalAvulsoMesAnterior + $totalFixoMesAnterior;

    //     $agendamentos = ScheduleModel::select('user_id', 'date')
    //     ->where([
    //         'status' => 'Finalizado',
    //         'faturado' => 1
    //     ])
    //     ->whereMonth('date', Carbon::now()->firstOfMonth()->subMonths()->format('m'))
    //     ->whereNull('data_nao_faturada_id')
    //     ->get()
    //     ->groupBy('user_id');

    //     try {
    //         DB::beginTransaction();

    //         foreach ($agendamentos as $key => $item) {
    //             $billing = BillingModel::where([
    //                 'user_id' => $key,
    //                 'month' => Carbon::parse($item->first()->date)->format('m'),
    //                 'qtd_schedule' => $concluidosAgendamentosMesAnterior,
    //                 'qtd_avulso' => $totalAvulsoMesAnterior,
    //                 'qtd_fixo' => $totalFixoMesAnterior,
    //                 'valor_avulso' => $valorAvulso,
    //                 'valor_fixo' => $valorFixo,
    //                 'valor_total' => $totalMesAnterior
    //             ])->first();

    //             if (empty($billing)) {

    //                 BillingModel::create([
    //                     'user_id' => $key,
    //                     'month' => Carbon::parse($item->first()->date)->format('m'),
    //                     'qtd_schedule' => $concluidosAgendamentosMesAnterior,
    //                     'qtd_avulso' => $totalAvulsoMesAnterior,
    //                     'qtd_fixo' => $totalFixoMesAnterior,
    //                     'valor_avulso' => $valorAvulso,
    //                     'valor_fixo' => $valorFixo,
    //                     'valor_total' => $totalMesAnterior
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return redirect()->route('settings.index')->with(['success' => true, 'message' => 'Faturamento gerado com sucesso!']);
    //     } catch (\Exception $e) {
    //         DB::rollback();
            
    //         return redirect()->route('settings.index')->with(['success' => false, 'message' => 'Ocorreu um erro ao gerar o faturamento!']);
    //     }
    // }
}
