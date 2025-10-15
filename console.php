<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Admin\TmdbController;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cron:sync-all-pending', function () {
    $this->info('Iniciando sincronização de todos os itens pendentes...');
    
    $controller = app(TmdbController::class);
    $key = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
    
    $response = $controller->cronSyncAllPending($key);
    
    if ($response->getStatusCode() === 403) {
        $this->error('Acesso não autorizado. Verifique a CRON_SYNC_KEY no .env');
        return 1;
    }
    
    $this->info($response->getContent());
    return 0;
})->purpose('Sincronizar todos os itens pendentes do calendário');

Artisan::command('cron:sync-recent-movies', function () {
    $this->info('Iniciando sincronização de filmes recentes...');
    
    $controller = app(TmdbController::class);
    $key = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
    
    $response = $controller->cronSyncRecentMovies($key);
    
    if ($response->getStatusCode() === 403) {
        $this->error('Acesso não autorizado. Verifique a CRON_SYNC_KEY no .env');
        return 1;
    }
    
    if ($response->getStatusCode() === 500) {
        $this->error($response->getContent());
        return 1;
    }
    
    $this->info($response->getContent());
    return 0;
})->purpose('Sincronizar filmes recentes do TMDB');

Artisan::command('cron:sync-recent-series', function () {
    $this->info('Iniciando sincronização de séries recentes...');
    
    $controller = app(TmdbController::class);
    $key = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
    
    $response = $controller->cronSyncRecentSeries($key);
    
    if ($response->getStatusCode() === 403) {
        $this->error('Acesso não autorizado. Verifique a CRON_SYNC_KEY no .env');
        return 1;
    }
    
    if ($response->getStatusCode() === 500) {
        $this->error($response->getContent());
        return 1;
    }
    
    $this->info($response->getContent());
    return 0;
})->purpose('Sincronizar séries recentes do TMDB');
