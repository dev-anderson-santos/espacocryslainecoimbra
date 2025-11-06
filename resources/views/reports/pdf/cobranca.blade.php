@extends('reports.pdf.template.template')
@section('conteudo')

{{-- <span style="font-weight: bold;">
    Relatório de Cobrança Mensal - {{ getMonths($mes) }} de {{ $ano }}
</span> --}}
<div class="clearfix">&nbsp;</div>
<div class="" id="tab_1" role="tabpanel" aria-labelledby="tab_x1">
    <table class="table">
        <thead>
            <tr>
                <th style="text-align: center" scope="col">Profissional</th>
                <th style="text-align: center" scope="col">CPF</th>
                <th style="text-align: center" scope="col">E-mail</th>
                <th style="text-align: center" scope="col">Telefone</th>
                <th style="text-align: center" scope="col">Endereço</th>
                <th style="text-align: center" scope="col">Agendamentos</th>
                <th style="text-align: center" scope="col">Valor a pagar</th>
                <th style="text-align: center" scope="col">Valor pago</th>
                <th style="text-align: center" scope="col">Status</th>
                {{-- <th style="text-align: center" scope="col">Status Atual</th> --}}
            </tr>
        </thead>
        <tbody>
            @if(!empty($clientes))
                @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente['name'] }}</td>
                    <td>{{ $cliente['cpf'] }}</td>
                    <td>{{ $cliente['email'] }}</td>
                    <td>{{ $cliente['phone'] }}</td>
                    <td>{{ $cliente['has_address']['address']['street'] }}, {{ $cliente['has_address']['address']['number'] }}, {{ $cliente['has_address']['address']['district'] }}, {{ $cliente['has_address']['address']['city'] }} - {{ $cliente['has_address']['address']['state'] }} - CEP: {{ $cliente['has_address']['address']['zipcode'] }}</td>
                    <td class="text-center">{{ $cliente['concluidosAgendamentosMesAnterior'] }}</td>
                    <td class="text-center">R$ {{ number_format($cliente['totalMesAnterior'], 2, ',', '.') }} + Taxa de R$ {{ number_format(20, 2, ',', '.') }}</td>
                    <td class="text-center">
                        R$ {{ $cliente['fatura_cliente'] != 0 ? $cliente['fatura_cliente'] : number_format($cliente['fatura_cliente'], 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @php
                            $charge = null;
                            $status = [];
                            $status['badge'] = 'warning';
                            $status['descricao'] = 'Pendente';

                            if ($cliente['fatura_cliente'] != 0 || ($cliente['fatura_cliente'] == 0 && !empty($cliente['fatura_cliente_id']))) {
                                $status['badge'] = 'success';
                                $status['descricao'] = 'Pago';
                            }
                        @endphp
                        <span class="badge badge-pill badge-{{$status['badge']}}">
                            {{ $status['descricao'] }}
                        </span>
                    </td>
                    {{-- <td></td> --}}
                </tr>
                @empty
                @endforelse
                {{-- <tr>
                    <td class="text-center text-bold">Totais:</td>
                    <td colspan="4"></td>
                    <td class="text-center text-bold">{{ array_sum(Arr::pluck($clientes, 'concluidosAgendamentosMesAnterior')) }}</td>
                    <td class="text-center text-bold">R$ {{ number_format(array_sum(Arr::pluck($clientes, 'totalMesAnteriorComTaxa')), 2, ',', '.') }}</td>
                    <td class="text-center text-bold">R$ {{ number_format(array_sum(Arr::pluck($clientes, 'fatura_cliente')), 2, ',', '.') }}</td>
                </tr> --}}
            @endif
        </tbody>
    </table>
    <p>
        Total de Profissionais: {{ count($clientes) }}<br>
        Total de Agendamentos: {{ array_sum(Arr::pluck($clientes, 'concluidosAgendamentosMesAnterior')) }}<br>
        Total a Receber: R$ {{ number_format(array_sum(Arr::pluck($clientes, 'totalMesAnterior')), 2, ',', '.') }}<br>     
    </p>
</div>
@endsection
@section('rodape')
    
@endsection
