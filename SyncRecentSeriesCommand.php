<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\TmdbController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncRecentSeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:sync-recent-series';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza séries recentes do TMDB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de séries recentes...');
        
        set_time_limit(3600);

        $controller = new TmdbController();

        try {
            $recentSeriesData = $controller->getRecentSeriesData();
            $newSeriesIds = collect($recentSeriesData['recentSeries'])->pluck('id')->all();

            if (empty($newSeriesIds)) {
                $this->info('Nenhuma série nova para sincronizar.');
                return Command::SUCCESS;
            }

            $this->info("Encontradas " . count($newSeriesIds) . " séries novas.");

            $created = 0; 
            $failed = 0; 
            $skipped = 0;

            $progressBar = $this->output->createProgressBar(count($newSeriesIds));
            $progressBar->start();

            foreach ($newSeriesIds as $tmdbId) {
                try {
                    $request = new Request(['tmdb_id' => $tmdbId, 'type' => 'tv']);
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
                    Log::error("CRON Sync (Séries) Falhou para TMDB ID {$tmdbId}: " . $e->getMessage());
                    $failed++;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            
            $summary = "Sincronização de séries via CLI concluída. Criados: {$created}, Ignorados: {$skipped}, Falhas: {$failed}.";
            $this->info($summary);
            Log::info($summary);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $errorMsg = "Erro no CRON de séries recentes: " . $e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg);
            return Command::FAILURE;
        }
    }
}
