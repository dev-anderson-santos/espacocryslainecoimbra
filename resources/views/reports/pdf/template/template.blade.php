<html>
    <head>
        <style>
            @page {margin: 60px 25px; counter-increment: page}

            header { position: fixed; top: -35px; left: 0px; right: 0px;  height: 50px; }
            footer { position: fixed; bottom: -20px; left: 0px; right: 0px;  height: 50px;}

            p { page-break-after: always; }
            p:last-child { page-break-after: never; }
            .pagenum::before {
                content: counter(page);
            }

            .pagetotal::after {
                content: .pagenum:content;
            }

            .pull-left {
                float: left;
            }

            .pull-right {
                float: right;
            }

            .texto-estado-cabecalho {
                text-transform: uppercase;
                margin-top: 8%;
                font-size: 14px;
                margin-left:220px;
                font-weight: 700;
            }

            /* .logo-dd {
                margin-top: 20%
            } */

            .box-cabecalho {
                border-bottom: 8px #000 solid;
                padding-bottom: 15px;
                margin-bottom: 5px;
                height: 105px;
            }

            .cabecalho-descricao,
            .tipo-classe
            {
                border: 3px solid #000;
            }
            body{
                padding-top: 100px;
            }

            .table {
                width: 100%;
                /* page-break-inside: avoid; */
            }

            /* table{
                page-break-inside: avoid;
            } */

            table > tr, td, th {
                text-align: left;
                padding: 5px;
            }

            thead > tr:first-child {
                background: #a6a6a6;
                color: #FFF;
            }

            tbody > tr:nth-child(odd) {
                background: #d9d9d9;
            }

            tbody > tr:nth-child(even) {
                background: #FFF;
            }

            .preenchido {
                font-size: 16px;
                text-transform: uppercase;
                color: #000;

            }

            fieldset {
                border-radius: 10px;
                padding: 10px;
                font-weight: bold;
                margin-bottom: 10px;
                min-height: 50px;
                page-break-inside: avoid;
            }

            span {
                font-weight: bold;
                margin-bottom: 10px;
            }

            .textarea {
                width: 95%;
                border: 1px solid #000;
                border-radius: 5px;
                font-size: 16px;
                text-transform: uppercase;
                color: #000;
                padding: 10px;
                page-break-inside: always;
            }
            .row {
                width: 99%;
                margin-bottom: 7px;
            }
            .table-cabecalho {
                border:1px solid #000;
                border-style: solid !important;
                border-spacing: 0;
                width: 100%;
            }

            .table-cabecalho td {
                background-color: #FFF !important;
                border:1px solid #000;
            }

            .label-local-ocorrencia {
                background-color: #bdbdbd;
            }

            .col-md-6 {
                float: left;
                width: 50%;
                text-align: left;
            }

            .col-md-12 {
                float: left;
                width: 100%;
                text-align: left;
            }

            ul li {
                list-style:none;
            }

            hr{
                width: 100%;
                margin-bottom: 20px;
            }
            .paginacao{
                color: red;
            }
        </style>
    </head>
    <body>
        <header>
            <div class="pull-left box-cabecalho" style="width: 100%; min-width: 100%;">
                <div class="pull-right">
                    <div class="pull-right logo-dd">
                        {{-- <img src="img/logo_dd.png" height="60"/> --}}
                        <span style="font-size: 11px;">Emissão em: {{ strftime("%d/%m/%Y - %H:%M") }}</span>
                    </div>
                </div>
                <div class="pull-left estado-bahia">
                    <div class="pull-left">
                        <img src="images/logo_email.jpeg" height="100"/>
                    </div>
                    <div class="pull-left texto-estado-cabecalho">
                        <i>{{ $titulo }} - {{ getMonths($mes) }} de {{ $ano }}</i><br>
                        {{-- Secretaria da segurança Pública - SSP<br>
                        Superintendência de Inteligência - SSP/GAB/SI --}}
                    </div>
                </div>
                
            </div>
        </header>
        
        {{-- <footer>
            @php
                setlocale(LC_TIME, 'portuguese');
                date_default_timezone_set('America/Sao_Paulo');
            @endphp
            <table class="table">
                <tr>
                    <td>Teste</td>
                    <td>{{ strftime("%d/%m/%Y - %H:%M:%S") }}</td>
                    <td width="20%">
                        <script type="text/php">
                            if (isset($pdf)) {
                                $text = "{PAGE_NUM} / {PAGE_COUNT} ";
                                $size = 10;
                                $font = $fontMetrics->getFont("Verdana");
                                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                                $x = ($pdf->get_width() - $width) - 20;
                                $y = $pdf->get_height() - 60;
                                $pdf->page_text($x, $y, $text, $font, $size);
                            }
                        </script>
                    </td>
                </tr>
            </table>
        </footer> --}}
        <main>
            @yield('conteudo')
            
        </main>

        <div style="text-align: right; width: 100%; min-width: 100%; position: absolute; bottom: 0; float: left;">
            {{-- @php
                setlocale(LC_TIME, 'portuguese');
                date_default_timezone_set('America/Sao_Paulo');
            @endphp
    
            <span style="font-size: 11px;">Emissão em: {{ strftime("%d/%m/%Y - %H:%M") }}</span>
    
            <br/>
            <span style="text-align: center;"> --}}
            @yield('rodape')
            <script type="text/php">
                    $text = "Página {PAGE_NUM} de {PAGE_COUNT} ";
                    $size = 10;
                    $font = $fontMetrics->getFont("Verdana");
                    $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                    $x = ($pdf->get_width() - $width) / 2;
                    $y = $pdf->get_height() - 35;
                    $pdf->page_text($x, $y, $text, $font, $size);
            </script>
        {{-- </div> --}}
    </body>
</html>
