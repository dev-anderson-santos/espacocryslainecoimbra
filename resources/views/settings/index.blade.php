@extends('adminlte::page')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Configurações</h1>
            </div>
        </div>
    </div>
</section>

<div class="card">
    <div class="card-body">

        @include('componentes.alerts')

        @include('componentes.alerts', [
            'type' => 'alert-info',
            'text' => 'Ao alterar os valores dos agendamentos, o valor informado será aplicado em todos os agendamentos do mês atual em diante.'
        ])

        <form action="{{ route('settings.update') }}" method="post" id="form-settings">
            @csrf
            <input type="hidden" name="id" value="{{ $setting->id ?? '' }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Valor do agendamento fixo:</label>
                        <input type="text" name="valor_fixo" class="form-control" value="{{ $setting->valor_fixo ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Valor do agendamento avulso:</label>
                        <input type="text" name="valor_avulso" class="form-control" value="{{ $setting->valor_avulso ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Horario limite para cancelar agendamento:</label>
                        <input type="time" name="hora_fechamento" class="form-control" value="{{ $setting->hora_fechamento ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Dia do vencimento:</label>
                        <input type="number" name="dia_fechamento" class="form-control" min="0" max="31" value="{{ $setting->dia_fechamento ?? '1' }}">
                    </div>
                </div>
                {{-- <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Data do vencimento:</label>
                        <input type="date" name="data_vencimento" class="form-control" value="{{ $setting->data_vencimento ?? '' }}">
                    </div>
                </div> --}}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Enviar notificação de agendamento para o e-mail abaixo:</label>
                        <input type="email" name="email_notificacao" class="form-control" value="{{ $setting->email_notificacao ?? '' }}">
                    </div>
                </div>
            </div>


            <fieldset>
                <legend>Datas não faturadas</legend>
                <div class="col-md-8 mb-2">
                    <button data-url="{{ route('settings.sync-dates') }}" id="btn-sync-dates" class="btn btn-primary btn-warning col-md-4" type="button">Sincronizar datas não faturadas</button>
                </div> 
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-hover table-sm" id="tabela-datas-nao-faturadas" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="display: none;">#</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($datas_nao_faturadas))
                                    @foreach ($datas_nao_faturadas as $item)
                                        <tr>
                                            <td style="display: none;">{{ $item->data }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->data)->isoFormat('dddd, DD \d\e MMMM \d\e Y') }}</td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="removerDataNaoFaturada('{{ $item->id }}')" class="btn btn-danger btn-sm btn-circle"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix">&zwnj;</div>
                    <div class="clearfix">&zwnj;</div>
                    {{-- <div class="col-md-12 text-right mt-3">
                        <button class="btn btn-primary btn-secondary" id="btn-adicionar-data-nao-faturada" type="button">Incluir Data Não Faturada</button>
                    </div> --}}
                </div>
            </fieldset>

            <div class="clearfix">&nbsp;</div>

            <fieldset>
                <legend>Agendamentos duplicados</legend>
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-hover table-sm" id="tabela-agendamentos-duplicados" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Sala</th>
                                    <th>Tipo de agendamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($duplicatedSchedules as $item)
                                    <tr>
                                        <td>{{ $item->user->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->date)->isoFormat('dddd, DD \d\e MMMM \d\e Y') }}</td>
                                        <td>{{ $item->hour->hour }}</td>
                                        <td>{{ $item->room->name }}</td>
                                        <td>{{ $item->tipo }}</td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix">&zwnj;</div>
                    <div class="clearfix">&zwnj;</div>
                </div>
            </fieldset>
            
            <div class="clearfix">&nbsp;</div>

            <div class="row">
                {{--
                <div class="col-md-12 mb-2">
                    <button class="btn btn-primary btn-secondary col-md-4" id="btn-adicionar-data-nao-faturada" type="button">Incluir Data Não Faturada</button>
                </div>
                <div class="col-md-12 mb-2">
                    <button class="btn btn-primary btn-success col-md-4" id="btn-faturar-agendamento" type="button">Faturar agendamentos</button>
                </div>
                <div class="col-md-12 mb-2">
                    <button class="btn btn-primary btn-warning col-md-4" id="btn-espelhar-agendamentos" type="button">Espelhar agendamentos</button>
                </div>
                <div class="col-md-12 mb-2">
                    <a href="{{ route('settings.generate-invoicing') }}" class="btn btn-primary btn-primary col-md-4" type="button">Gerar faturamento do mês</a>
                </div> 
                --}}
                <div class="col-md-12 mb-2" style="display: none">
                    <button class="btn btn-primary btn-warning col-md-4" id="btn-excluir-agendamentos-duplicados" type="button">Excluir agendamentos duplicados</button>
                </div>
                <div class="col-md-12 mb-2" style="display: none">
                    <button class="btn btn-primary btn-primary col-md-4" id="btn-update-schedules-price-manually" type="button">Atualizar valor dos agendamentos</button>
                </div>
            </div>

            <div class="clearfix">&nbsp;</div>
            {{-- <div class="row"> --}}
                <div class="float-right">
                    <button class="btn btn-primary btn-secondary" id="btn-adicionar-data-nao-faturada" type="button">Incluir Data Não Faturada</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            {{-- </div> --}}
        </form>
    </div>
</div>
@endsection