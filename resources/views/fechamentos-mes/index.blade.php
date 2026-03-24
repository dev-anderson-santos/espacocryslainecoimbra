@extends('adminlte::page')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Fechamento do mês {{ $user_name }}</h1>
            </div>
        </div>
    </div>
</section>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">{{ now()->isoFormat('MMMM') }}</span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">Concluídos Parcial: </span> {{ $concluidosParcialAgendamentos }} Horários
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">Total Parcial: </span> R$ {{ number_format($totalParcialValor, 2, ',', '.') }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="javascript:void(0)" class="btn btn-info" onclick="modalGlobalOpen('{{ route('schedule.details', ['user_id' => $id, 'schedule_type' => 'MES_ATUAL']) }}', 'Detalhes do mês de {{ now()->isoFormat('MMMM') }}')">Ver Detalhes</a>
            </div>
        </div>

        <div class="clearfix">&nbsp;</div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">{{ now()->firstOfMonth()->subMonths()->isoFormat('MMMM') }}</span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">Concluídos: </span> {{ $concluidosAgendamentosMesAnterior }} Horários
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">Total: </span> R$ {{ number_format($totalMesAnterior, 2, ',', '.') }}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span style="font-weight: 800">Vencimento: Todo dia {{ $setting->dia_fechamento }} do mês</span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a href="javascript:void(0)" class="btn btn-info" onclick="modalGlobalOpen('{{ route('schedule.details', ['user_id' => $id, 'schedule_type' => 'MES_ANTERIOR']) }}', 'Detalhes do mês de {{ now()->subMonths()->isoFormat('MMMM') }}')">Ver Detalhes</a>
            </div>
        </div>
    </div>
</div>

@endsection