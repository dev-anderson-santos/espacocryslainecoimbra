<?php

use App\User;
use App\Models\ImagemSalaModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Admin\AdministratorController;

Route::get('/', function () {
    $imagensSala = ImagemSalaModel::orderBy('order_image', 'ASC')->get()->map(function($image) {
        $image->filepath = Storage::disk('local')->url('images-room-institutional/'.$image->filename);
        return $image;
    });

    // dd(ImagemSalaModel::find(2));
    return view('index.index', ['imagensSala' => $imagensSala]);
})->name('index');

Auth::routes();
Route::post('/login', [
    'uses'          => 'Auth\LoginController@login',
    'middleware'    => 'checkstatus',
]);

Route::group(['prefix' => 'app'],function () {

    Route::group(['prefix' => 'user'], function () {
        Route::get('/clients', 'UserController@index')->middleware('auth')->name('user.index');
        Route::get('/new', 'UserController@create')->name('user.guest-create');
        Route::post('/store', 'UserController@store')->name('user.store');
        Route::get('/edit/{user_id}', 'UserController@edit')->middleware('auth')->name('user.edit');
        Route::put('/update', 'UserController@update')->middleware('auth')->name('user.update');
    });

    Route::group(['middleware' => 'auth'], function () {
        Route::group(['prefix' => 'schedule'], function() {
            Route::get('/', 'ScheduleController@index')->name('schedule.index');
            Route::get('/modal-schedule', 'ScheduleController@modalSchedule')->name('schedule.modal-schedule');
            Route::post('/to-schedule', 'ScheduleController@store')->name('schedule.store');
            Route::post('/', 'ScheduleController@showSpecificShcedule')->name('schedule.show-specific-shedule');
            Route::post('/to-destroy-schedule', 'ScheduleController@destroy')->name('schedule.destroy');
            Route::get('/user-schedules/{user_id?}', 'ScheduleController@userSchedules')->name('schedule.user-schedules');
            Route::get('/fechamento-mes/{user_id?}', 'ScheduleController@fechamentosDoMes')->name('schedule.fechamento-mes');
            Route::post('/mudar-tipo-agendamento', 'ScheduleController@mudarTipoAgendamento')->name('schedule.mudar-tipo-agendamento');
            Route::get('/update-all-schedules', 'ScheduleController@updateAllSchedules')->name('schedule.update-all-schedules');
            Route::get('/details', 'ScheduleController@details')->name('schedule.details');
            Route::post('/destroy-schedule-next-month', 'ScheduleController@destroyNextMonth');
            Route::post('/mudar-tipo-agendamento-proximo-mes', 'ScheduleController@mudarTipoAgendamentoProximoMes');
            Route::post('/cancel-all-fixed-schedules/{user_id?}', 'ScheduleController@cancelAllFixedSchedules');
            Route::post('/cancel-all-fixed-next-month-schedules/{user_id?}', 'ScheduleController@cancelAllFixedNextMonthSchedules');
            Route::get('/modal-cancelar-agendamento-fixo', 'ScheduleController@modalCancelarAgendamentoFixo')->name('schedule.modal-cancelar-agendamento-fixo');
            Route::post('/cancelar-agendamento-fixo', 'ScheduleController@cancelarAgendamentoFixo')->name('schedule.cancelar-agendamento-fixo');
            // Route::get('/index-administrador', 'ScheduleController@indexAdmin')->name('schedule.index-administrador');
            // Route::post('/index-administrador', 'ScheduleController@showSpecificShceduleAdministrador')->name('schedule.show-specific-shedule-administrador');
            // Route::get('/schedule-search', 'ScheduleController@scheduleSearch')->name('schedule.search');
            // Route::post('/schedule-search', 'ScheduleController@showSpecificShceduleMonth')->name('schedule.show-specific-shedule-month');
        });
    
        Route::group(['middleware' => 'is_admin'], function () {
            Route::group(['prefix' => 'settings'], function() {
                Route::get('/', 'SettingsController@index')->name('settings.index');
                Route::post('/', 'SettingsController@update')->name('settings.update');
                Route::get('/update-settings-ajax', 'SettingsController@updateSettingsAjax')->name('settings.update-settings-ajax');
                Route::get('/modal-adicionar-data-nao-faturada', 'SettingsController@modalAdicionarDataNaoFaturada')->name('settings.adicionarDataNaoFaturada');
                Route::post('/salvar-data-nao-faturada', 'SettingsController@adicionarDataNaoFaturada')->name('settings.salvarDataNaoFaturada');
                Route::get('/remover-data-nao-faturada', 'SettingsController@removerDataNaoFaturada')->name('settings.removerDataNaoFaturada');
                Route::get('/faturar-agendamentos', 'SettingsController@faturar')->name('settings.faturar');
                Route::get('/espelhar-agendamentos', 'SettingsController@mirror')->name('settings.mirror');
                Route::get('/excluir-agendamentos-espelhados', 'SettingsController@deleteMirroredSchedules')->name('settings.removerDataNaoFaturada');
                Route::get('/generate-invoicing', 'SettingsController@generateInvoicing')->name('settings.generate-invoicing');
                Route::get('/delete-duplicated-schedules', 'SettingsController@deleteDuplicatedSchedules')->name('settings.delete-duplicated-schedules');
                Route::get('/update-schedules-price-manually', 'SettingsController@updateSchedulesPriceManually')->name('settings.update-schedules-price-manually');
                Route::get('/sync-dates', 'SettingsController@syncDates')->name('settings.sync-dates');

                Route::group(['prefix' => 'institutional'], function() {
                    Route::get('/', 'InstitutionalController@index')->name('.institutional.index');
                    Route::get('/modal-adicionar-imagem-institucional', 'InstitutionalController@modalAdicionarImagem')->name('.institutional.modal-adicionar-imagem-institucional');
                    Route::post('/save-image', 'InstitutionalController@store')->name('.institutional.save-image');
                    Route::post('/edit-image', 'InstitutionalController@edit')->name('.institutional.edit-image');
                    Route::get('/delete-institutional-image', 'InstitutionalController@deleteInstitutionalImage')->name('.institutional.delete-institutional-image');
                });
            });

            Route::group(['prefix' => 'rooms'], function() {
                Route::get('/', 'RoomController@index')->name('room.index');
                Route::get('/modal-adicionar-sala', 'RoomController@create')->name('room.create');
                Route::get('/modal-editar-sala/{id}', 'RoomController@edit')->name('room.edit');
                Route::post('/atualizar-sala', 'RoomController@update')->name('room.update');
                Route::post('/adicionar-sala', 'RoomController@store')->name('room.store');
                Route::get('/verificar-sala-em-uso', 'RoomController@verificarSalaEmUso')->name('room.check-room-in-use');
                Route::get('/remover-sala', 'RoomController@destroy')->name('room.destroy');
            });
        });

        // Route::group(['prefix' => 'reports', 'middleware' => 'is_admin'], function () {
        //     Route::get('/', 'ReportsController@index')->name('reports.index');
        //     Route::post('/', 'ReportsController@showSpecificInvoicing')->name('reports.show-specific-invoicing');
        // });

        // Route::group(['prefix' => 'admin', 'middleware' => 'is_admin', 'as' => 'admin'], function () {
        //     Route::get('/dashboard', [AdministratorController::class, 'dashboard'])->name('.dashboard');
        // });

        Route::group(['prefix' => 'admin', 'middleware' => 'is_admin', 'as' => 'admin'], function () {
            Route::get('/dashboard', [AdministratorController::class, 'dashboard'])->name('.dashboard');

            Route::group(['prefix' => 'finance'], function() {
                Route::get('/charge', 'FinanceController@index')->name('finance.charge');
                Route::post('/search-charges', 'FinanceController@searchChargesByMonth')->name('.finance.search-charges');
                Route::get('/modal-registrar-pagamento', 'FinanceController@modalRegitrarPagamento')->name('.finance.modal-registrar-pagamento');
                Route::post('/registrar-pagamento', 'FinanceController@registrarPagamento')->name('.finance.registrar-pagaamento');
            });

            Route::group(['prefix' => 'help'], function() {
                Route::get('/release-notes', 'Admin\AdministratorController@releaseNotes')->name('help.release-notes');
            });

            Route::group(['prefix' => 'reports'], function() {
                Route::get('/cobranca', 'FinanceController@relatorioCobranca')->name('.reports.cobranca');
            });

            
        });

        Route::group(['prefix' => 'admin', 'middleware' => 'is_super_admin', 'as' => 'admin'], function () {
            Route::group(['prefix' => 'reports'], function () {
                Route::get('/yield-per-period', 'Admin\ReportsController@yeldsPerPeriodIndex')->name('.reports.yelds-per-period-index');
                Route::post('/yield-per-period', 'Admin\ReportsController@yeldsPerPeriod')->name('.reports.yield-per-period');
                Route::get('/yield-per-customer', 'Admin\ReportsController@yeldsPerCustomerIndex')->name('.reports.yield-per-customer-index');
                Route::post('/yield-per-customer', 'Admin\ReportsController@yeldsPerCustomer')->name('.reports.yield-per-customer');
                // Route::post('/', 'Admin\ReportsController@showSpecificInvoicing')->name('reports.show-specific-invoicing');
            });
        });
    });
});

Route::get('/client-theme.css', function () {
    $hex = config('client.primary_hex', '#754C9A'); // RGB (117, 76, 154)
    $hexSecondary = config('client.secondary_hex', '#FFF');
    $hexHighlight = config('client.highlight_color', '#9a72a8a4');

    $css = <<<CSS
    :root { 
        --client-color: {$hex}; 
        --client-color-secondary: {$hexSecondary};
        --client-highlight-color: {$hexHighlight};
        --client-link: var(--client-color);
        --client-link-hover: #5b2b10; /* ou o hover que preferir */
    }

    .main-sidebar.bg-custom-sidebar {
        background-color: var(--client-color-secondary) !important;
    }

    .main-sidebar.bg-custom-sidebar .nav-link,
    .main-sidebar.bg-custom-sidebar .brand-link {
        color: var(--client-color) !important;
    }

    .main-sidebar.bg-custom-sidebar .nav-link:hover,
    .main-sidebar.bg-custom-sidebar .nav-link:focus {
        background-color: #d8dccdff; /* Cor de destaque para o hover */
        color: white; /* Cor do texto no hover */
    }

    /* Adicione estas classes para o item de menu ativo */
    .nav-sidebar .nav-item > .nav-link.active,
    .nav-sidebar .nav-item > .nav-link.active:hover {
        background-color: var(--client-highlight-color) !important;
        color: white !important; /* Para garantir contraste com a cor de destaque */
    }

    /* Card na cor do cliente */
    .card-outline.card-client { border-top: 3px solid var(--client-color) !important; }
    .card-client .card-title,
    .card-client .card-header a,
    .card-client .card-tools .btn-link { color: var(--client-color) !important; }

    /* Botão na cor do cliente */
    .btn-client {
    background-color: var(--client-color-secondary) !important;
    border-color: var(--client-color) !important;
    color: var(--client-color) !important;
    }
    .btn-client:hover,
    .btn-client:focus { filter: brightness(0.92); color: #fff !important; }

    /* Utilitários (opcional) */
    .bg-client   { background-color: var(--client-color) !important; color:#fff !important; }
    .text-client { color: var(--client-color) !important; }
    .border-client { border-color: var(--client-color) !important; }
    CSS;

    return response($css, 200)->header('Content-Type', 'text/css');
})->name('client-theme-css');

