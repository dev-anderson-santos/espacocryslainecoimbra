<?php

use App\User;
use Carbon\Carbon;
use App\Models\ScheduleModel;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
// use App\Models\DenunciaAtendimentoModel;

if(!function_exists('removeCaracteresEspeciais')) {
    /**
     * Retorna a string informada sem caracteres especiais.
     *
     * @param string $string
     * @return string|null
    */
    function removeCaracteresEspeciais($string) {

        $_string = iconv( "UTF-8" , "ASCII//TRANSLIT//IGNORE" , $string );
        $_string = preg_replace(['/[ ]/' , '/[^A-Za-z0-9\-]/'] , ['' , ''], $_string);
        $_string = str_replace('-', '', $_string);

        return $_string != '' ? $_string : null;
    }
}

if(!function_exists('generatePassword')) {

    function generatePassword($qtyCaraceters = 8)
    {
        //Letras minúsculas embaralhadas
        $smallLetters = str_shuffle('abcdefghijklmnopqrstuvwxyz');

        //Letras maiúsculas embaralhadas
        $capitalLetters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');

        //Números aleatórios
        $numbers = (((date('Ymd') / 12) * 24) + mt_rand(800, 9999));
        $numbers .= 1234567890;

        //Caracteres Especiais
        $specialCharacters = str_shuffle('!@#$%*-');

        //Junta tudo
        $characters = $capitalLetters . $smallLetters . $numbers . $specialCharacters;

        //Embaralha e pega apenas a quantidade de caracteres informada no parâmetro
        $password = substr(str_shuffle($characters), 0, $qtyCaraceters);

        //Retorna a senha
        return $password;
    }
}

if (!function_exists('maskCPF')) {
    /**
     * Adiciona a mascara $mask no $str
     *
     * @param $mask ###.###.###-##
     * @param $str
     * @return mixed
     */
    function maskCPF($mask, $str)
    {
        $str = str_replace(" ", "", $str);
        for ($i = 0; $i < strlen($str); $i++) {
            if($i < 7) {
                $mask[ strpos($mask, "#") ] = 'X';    
            } else {
                $mask[ strpos($mask, "#") ] = $str[ $i ];
            }
        }

        return $mask;
    }
}

if (!function_exists('mask')) {
    /**
     * Adiciona a mascara $mask no $str
     *
     * @param $mask ###.###.###-##
     * @param $str
     * @return mixed
     */
    function mask($mask, $str)
    {
        // dd($mask, $str);
        $str = str_replace(" ", "", $str);
        for ($i = 0; $i < strlen($str); $i++) {
            $mask[ strpos($mask, "#") ] = $str[ $i ];
        }

        return $mask;
    }
}

// if(!function_exists('perfil')) {
//     function perfil($perfil = 0|[])
//     {   
//         $perfilTipoUsuario =  \App\Models\UsuarioModel::find(session('id_usuario'))->tipoUsuario->perfil;
//         if(is_array($perfil))
//             return in_array($perfilTipoUsuario, $perfil);
            
//         return $perfil == $perfilTipoUsuario;
//     }
// }

// if(!function_exists('perfilExcept')) {
//     function perfilExcept($perfil = 0|[])
//     {   
//         $perfilTipoUsuario =  \App\Models\UsuarioModel::find(session('id_usuario'))->tipoUsuario->perfil;
//         if(is_array($perfil))
//             return !in_array($perfilTipoUsuario, $perfil);
            
//         return $perfil != $perfilTipoUsuario;
//     }
// }

// if(!function_exists('notificacoes')) {
//     function notificacoes()
//     {   
//         $notificacoes = \App\Models\NotificacoesModel::where([
//             'id_usuario' => session()->get('id_usuario'),
//             'lido_em' => NULL 
//         ])->get();
//         return $notificacoes;
//     }
// }

if(!function_exists('getEstados')) {
    function getEstados(string $sigla = NULL)
    {   
        $estados = [
            "AC" => "Acre",
            "AL" => "Alagoas",
            "AP" => "Amapá",
            "AM" => "Amazonas",
            "BA" => "Bahia",
            "CE" => "Ceará",
            "DF" => "Distrito Federal",
            "ES" => "Espírito Santo",
            "GO" => "Goiás",
            "MA" => "Maranhão",
            "MT" => "Mato Grosso",
            "MS" => "Mato Grosso do Sul",
            "MG" => "Minas Gerais",
            "PA" => "Pará",
            "PB" => "Paraíba",
            "PR" => "Paraná",
            "PE" => "Pernambuco",
            "PI" => "Piauí",
            "RJ" => "Rio de Janeiro",
            "RN" => "Rio Grande do Norte",
            "RS" => "Rio Grande do Sul",
            "RO" => "Rondônia",
            "RR" => "Roraima",
            "SC" => "Santa Catarina",
            "SP" => "São Paulo",
            "SE" => "Sergipe",
            "TO" => "Tocantins",
        ];

        return is_null($sigla) ? $estados : $estados[$sigla];
    }
}

if(!function_exists('getMonths')) {
    function getMonths(string $mes = NULL)
    {   
        $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro',
        ];

        return is_null($mes) ? $meses : $meses[$mes];
    }
}

if(!function_exists('denunciaAtendimentoStatus')) {
    function denunciaAtendimentoStatus(string $item = NULL)
    {   
        $status = [
            'AGUARDANDO_REVISAO' => 'Aguardando Revisão',
            'AGUARDANDO_CORRECAO' => 'Aguardando Correção',
            'AGUARDANDO_DIFUSAO' => 'Aguardando Difusão',
            'DIFUNDIDA' => 'Difundida',
            'AGUARDANDO_RESULTADOS' => 'Aguardando Resultados',
            'AGUARDANDO_ANALISE' => 'Aguardando Análise',
            'ARQUIVADA' => 'Arquivada',
            'FINALIZADA' => 'Finalizada',
            'EM_EDICAO' => 'Em cadastramento',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

if(!function_exists('pelesEnvolvidos')) {
    function pelesEnvolvidos(string $item = NULL)
    {   
        $status = [
            'branco' => 'Branco',
            'negro' => 'Negro',
            'pardo' => 'Pardo',
            'desconhece' => 'Desconhece',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

if(!function_exists('estaturasEnvolvidos')) {
    function estaturasEnvolvidos(string $item = NULL)
    {   
        $status = [
            'alta' => 'Alta',
            'baixa' => 'Baixa',
            'mediana' => 'Mediana',
            'desconhece' => 'Desconhece',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

if(!function_exists('portesEnvolvidos')) {
    function portesEnvolvidos(string $item = NULL)
    {   
        $status = [
            'magro' => 'Magro',
            'normal' => 'Normal',
            'gordo' => 'Gordo',
            'desconhece' => 'Desconhece',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

if(!function_exists('cabelosEnvolvidos')) {
    function cabelosEnvolvidos(string $item = NULL)
    {   
        $status = [
            'pretos' => 'Pretos',
            'castanhos' => 'Castanhos',
            'loiros' => 'Loiros',
            'ruivos' => 'Ruivos',
            'grisalhos' => 'Grisalhos',
            'careca' => 'Careca',
            'desconhece' => 'Desconhece',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

if(!function_exists('olhosEnvolvidos')) {
    function olhosEnvolvidos(string $item = NULL)
    {   
        $status = [
            'pretos' => 'Pretos',
            'castanhos' => 'Castanhos',
            'verdes' => 'Verdes',
            'azuis' => 'Azuis',
            'desconhece' => 'Desconhece',
        ];

        return is_null($item) ? $status : $status[$item];
    }
}

// if(!function_exists('updateStatusDenunciaAtendimento')) {
//     function updateStatusDenunciaAtendimento(DenunciaAtendimentoModel $atendimento)
//     {   
//         if($atendimento->tipo == 'DENUNCIA') {
//             $dados = [];
//             if ($atendimento->status == 'AGUARDANDO_CORRECAO' && perfil(['Administrador', 'Supervisor', 'Operador'])) {
//                 $dados['status'] = 'AGUARDANDO_REVISAO';
//                 $dados['corrigido_por'] = session()->get('id_usuario');
//                 $dados['observacoes_para_correcao'] = NULL;
                
//             } else if ($atendimento->status == 'AGUARDANDO_DIFUSAO' && perfil(['Administrador', 'Supervisor', 'Difusor'])) {
//                 $dados['status'] = 'AGUARDANDO_REVISAO';
//                 $dados['corrigido_por'] = session()->get('id_usuario');
//                 $dados['observacoes_do_difusor'] = NULL;
//             }
    
//             return $dados;
//         }
        
//         return [];
//     }
// }

if(!function_exists('formatarLog')) {
    function formatarLog($log)
    {   
        $space = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $str = '';
        foreach (json_decode($log) as $k => $item) {
            if($k == 'text') {
                $str = $item;
            } else {
                foreach ($item as $key => $value) {
                    $str = $str . strtoupper(str_replace('_', ' ', $key)) . ':<br>';
                    $str = $str . $space . '<strong>de:</strong> '. $value->before . '<br>';
                    $str = $str . $space . '<strong>para:</strong> '. $value->after . '<br>';
                }
            }
        }
        return $str;
    }
}

if(!function_exists('origemDenuncia')) {
    function origemDenuncia(string $item = NULL)
    {   
        $origem = [
            'INTERNA' => 'Interna',
            '180' => '180',
            'DENUNCIE_AQUI' => 'Denuncie Aqui'
        ];

        return is_null($item) ? $origem : $origem[$item];
    }
}

if (!function_exists('getVersion')) {
    /**
     * Busca a versão do sistema
     *
     * @return string
     */
    function getVersion()
    {
        $content = file_get_contents(base_path('package.json'));
        $content = json_decode($content,true);
        
        return $content['version'];
    }
}

// if (!function_exists('gerarProtocolo')) {
//     /**
//      * Gera o protocolo de atendimento
//      *
//      * @param array $dados
//      * @return string
//      */
//     function gerarProtocolo(array $dados)
//     {
//         $tipo = $dados['tipo'] == 'ATENDIMENTO' ? 'A' : 'D';

//         $denunciaAux = DenunciaAtendimentoModel::{strtolower($dados['tipo'])}()->latest()->first();
//         $sequencia = sprintf('%05d', DenunciaAtendimentoModel::{strtolower($dados['tipo'])}()->whereNotNull('protocolo')->get()->count() + 1);
        
//         if ($denunciaAux) {
//             $mesCriacaoAtendimento = Carbon::parse($denunciaAux->created_at)->format('m');
//             $mesAtual = Carbon::now()->format('m');

//             if(($mesAtual > $mesCriacaoAtendimento) || ($mesCriacaoAtendimento == 12 && $mesAtual == 1)) {
//                 $sequencia = sprintf('%05d', 1);
//             }
//         }

//         $data = Carbon::now()->format('m.Y');
        
//         return "$tipo$sequencia.$data";
            
//     }
// }

if (!function_exists('getWeekDays')) {
    function getWeekDays(string $referenceDay)
    {
        $arrDays = [];
        $weekOfMonth = Carbon::parse($referenceDay)->endOfMonth()->weekOfMonth;

        if (Carbon::parse($referenceDay)->isNextMonth()) {
            for ($i = Carbon::parse($referenceDay)->weekOfMonth; $i <= $weekOfMonth; $i++) {
                if($i == Carbon::parse($referenceDay)->weekOfMonth) {
                    $arrDays[$i] = $referenceDay;

                    if (Carbon::parse($arrDays[$i])->addMonth()->isNextMonth()) {
                        break;
                    }
                } else {
                    $arrDays[$i] = Carbon::parse($arrDays[$i-1])->addDays(7)->format('Y-m-d');

                    if (Carbon::parse($arrDays[$i])->format('m') != Carbon::parse($arrDays[$i-1])->format('m')) {
                        unset($arrDays[$i]);
                        break;
                    }
                }
            }
        } else {
            for ($i = Carbon::parse($referenceDay)->weekOfMonth; $i <= $weekOfMonth; $i++) {
                if($i == Carbon::parse($referenceDay)->weekOfMonth) {
                    $arrDays[$i] = $referenceDay;
    
                    if (Carbon::parse($arrDays[$i])->isNextMonth()) {
                        break;
                    }
                } else {
                    $arrDays[$i] = Carbon::parse($arrDays[$i-1])->addDays(7)->format('Y-m-d');
                    if (Carbon::parse($arrDays[$i-1])->format('m') != Carbon::parse($arrDays[$i])->format('m')) {
                        unset($arrDays[$i]);
                        break;
                    }
                }
            }
        }
        

        return $arrDays;
    }
}

if (!function_exists('getWeekDaysNextMonth')) {
    function getWeekDaysNextMonth(string $referenceDay)
    {
        $arrDays = [];
        $weekOfMonth = Carbon::parse($referenceDay)->endOfMonth()->weekOfMonth;
        for ($i = Carbon::parse($referenceDay)->weekOfMonth; $i <= $weekOfMonth; $i++) {
            if($i == Carbon::parse($referenceDay)->weekOfMonth) {
                $arrDays[$i] = $referenceDay;

                if (Carbon::parse($arrDays[$i])->isLastWeek()) {
                    break;
                }
            } else {
                $arrDays[$i] = Carbon::parse($arrDays[$i-1])->addDays(7)->format('Y-m-d');

                if (Carbon::parse($arrDays[$i])->format('m') != Carbon::parse($arrDays[$i-1])->format('m')) {
                    unset($arrDays[$i]);
                    break;
                }
            }
        }

        return $arrDays;
    }
}

if (!function_exists('getUsers')) {
    function getUsers()
    {
        return (new User())::orderBy('name', 'ASC')->get();
    }
}

if (!function_exists('faturar')) {
    function faturar()
    {
        try {
            DB::beginTransaction();

            $totalSchedules = 0;

            if ((now()->format('d') == 1)) {
                $schedules = ScheduleModel::where('status', 'Finalizado')
                    ->where('faturado', '!=', 1)
                    ->whereNull('data_nao_faturada_id')
                    ->whereBetween('date', [
                        now()->subMonth()->startOfMonth()->format('Y-m-d'),
                        now()->subMonth()->endOfMonth()->format('Y-m-d')
                    ]);
    
                $totalSchedules = $schedules->get()->count();
                if ($totalSchedules > 0) {
                    $schedules->update(['faturado' => 1]);
                    DB::commit();
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            // $e->getMessage();
        }
    }
}

if(!function_exists('excel')) {

    // function excel(string $view, string $fileName, $data = null)
    // {
    //     return \Excel::download(new \App\Exports\ExcelExport($view, $data), "{$fileName}.xlsx");
    // }
}

if(!function_exists('pdf')) {

    function pdf($view, string $paper = 'a4', string $format = 'landscape', bool $showPagination = false)
    {
        return Pdf::loadHtml($view)->setPaper($paper, $format)->setOption(['enable_php' => $showPagination]);
            // ->setOptions([
            //     'isRemoteEnabled' => true
            // ]);
    }
}