<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\TmdbController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncRecentMoviesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:sync-recent-movies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza filmes recentes do TMDB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de filmes recentes...');
        
        set_time_limit(3600);

        $controller = new TmdbController();

        try {
            $recentMoviesData = $controller->getRecentMoviesData();
            $newMovieIds = collect($recentMoviesData['recentMovies'])->pluck('id')->all();

            if (empty($newMovieIds)) {
                $this->info('Nenhum filme novo para sincronizar.');
                return Command::SUCCESS;
            }

            $this->info("Encontrados " . count($newMovieIds) . " filmes novos.");

            $created = 0; 
            $failed = 0; 
            $skipped = 0;

            $progressBar = $this->output->createProgressBar(count($newMovieIds));
            $progressBar->start();

            foreach ($newMovieIds as $tmdbId) {
                try {
                    $request = new Request(['tmdb_id' => $tmdbId, 'type' => 'movie']);
                    $storeResponse = $controller->store($request);
                    $status = $storeResponse->getStatusCode();

                    if ($status === 200) {
                        $created++;
                    } elseif ($status === 208) {
                        $skipped++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    Log::error("CRON Sync (Filmes) Falhou para TMDB ID {$tmdbId}: " . $e->getMessage());
                    $failed++;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            
            $summary = "Sincronização de filmes via CLI concluída. Criados: {$created}, Ignorados: {$skipped}, Falhas: {$failed}.";
            $this->info($summary);
            Log::info($summary);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $errorMsg = "Erro no CRON de filmes recentes: " . $e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg);
            return Command::FAILURE;
        }
    }
}
