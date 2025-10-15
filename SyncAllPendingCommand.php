<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\TmdbController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncAllPendingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:sync-all-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza todos os itens pendentes do calendário TMDB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de todos os itens pendentes...');
        
        set_time_limit(3600);

        $controller = new TmdbController();
        
        try {
            $calendarData = $controller->getEnrichedCalendarData();
            $items = $calendarData['calendarByMonth']->flatten(1);
            
            $pendingItems = $items->filter(fn($item) => ($item['local_status'] ?? 'Pendente') === 'Pendente')->unique('tmdb_id');

            if ($pendingItems->isEmpty()) {
                $this->info('Nenhum item pendente para sincronizar.');
                return Command::SUCCESS;
            }
            
            $this->info("Encontrados {$pendingItems->count()} itens pendentes.");
            
            $created = 0; 
            $updated = 0; 
            $failed = 0; 
            $skipped = 0;

            $progressBar = $this->output->createProgressBar($pendingItems->count());
            $progressBar->start();

            foreach ($pendingItems as $item) {
                try {
                    $request = new Request(['tmdb_id' => $item['tmdb_id'], 'type' => 'tv']);
                    $response = $controller->store($request);
                    $status = $response->getStatusCode();

                    if ($status === 200) {
                        if (str_contains(optional($response->getData())->message, 'updated')) {
                            $updated++;
                        } else {
                            $created++;
                        }
                    } elseif ($status === 208) {
                        $skipped++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    Log::error("CRON Sync (Calendário) Falhou para TMDB ID {$item['tmdb_id']}: " . $e->getMessage());
                    $failed++;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            
            $summary = "Sincronização (Calendário) via CLI concluída. Criados: {$created}, Atualizados: {$updated}, Ignorados: {$skipped}, Falhas: {$failed}.";
            $this->info($summary);
            Log::info($summary);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $errorMsg = "Erro na sincronização: " . $e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg);
            return Command::FAILURE;
        }
    }
}
