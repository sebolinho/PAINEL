<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostEpisode;
use App\Models\Tag;
use App\Traits\PostTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TmdbController extends Controller
{
    use PostTrait;

    /**
     * Exibe a página principal da ferramenta TMDB, incluindo o calendário e filmes recentes.
     */
    public function show(Request $request)
    {
        $config = [
            'title' => __('Tool'),
            'nav' => 'tool',
        ];
        if (!config('settings.tmdb_api') || !config('settings.tmdb_language')) {
            return redirect()->route('admin.tmdb.settings');
        }

        $calendarData = $this->getEnrichedCalendarData();
        $recentMoviesData = $this->getRecentMoviesData();
        $recentSeriesData = $this->getRecentSeriesData();
        $firstImportData = $this->getFirstImportData();

        return view('admin.tmdb.show', array_merge(
            compact('config', 'request'),
            $calendarData,
            $recentMoviesData,
            $recentSeriesData,
            $firstImportData
        ));
    }
    
    /**
     * Busca dados no TMDB e exibe os resultados junto com o calendário e filmes recentes.
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'type' => 'required|in:movie,tv',
            'q' => 'nullable|string',
            'sortable' => 'nullable|string',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($request->isMethod('post')) {
            return redirect()->route('admin.tmdb.fetch', [
                'type' => $request->input('type'),
                'q' => $request->input('q'),
                'sortable' => $request->input('sortable'),
                'page' => $request->input('page', 1)
            ]);
        }

        $config = ['title' => __('Tool'), 'nav' => 'tool'];
        $listings = [];
        $result = [];
        $apiResult = [];

        if ($request->has('type')) {
            $q = $request->input('q');
            $sortable = $request->input('sortable');
            $page = $request->input('page', 1);
            $results = [];

            if ($q) {
                if (preg_match('/^tt\d+$/', $q)) {
                    $apiUrl = 'https://api.themoviedb.org/3/find/' . $q;
                    $apiParams = ['api_key' => config('settings.tmdb_api'), 'language' => config('settings.tmdb_language'), 'external_source' => 'imdb_id'];
                    $response = Http::get($apiUrl, $apiParams);
                    if ($response->successful()) {
                        $apiResult = $response->json();
                        $results = $apiResult[$request->type . '_results'] ?? [];
                    }
                } elseif (is_numeric($q)) {
                    $apiUrl = 'https://api.themoviedb.org/3/' . $request->type . '/' . $q;
                    $apiParams = ['api_key' => config('settings.tmdb_api'), 'language' => config('settings.tmdb_language')];
                    $response = Http::get($apiUrl, $apiParams);
                    if ($response->successful()) {
                        $apiResult = ['results' => [$response->json()], 'total_results' => 1, 'total_pages' => 1];
                        $results = $apiResult['results'];
                    }
                } else {
                    $apiUrl = 'https://api.themoviedb.org/3/search/' . $request->type;
                    $apiParams = ['query' => $q, 'api_key' => config('settings.tmdb_api'), 'language' => config('settings.tmdb_language'), 'page' => $page];
                    $response = Http::get($apiUrl, $apiParams);
                    $apiResult = $response->json();
                    $results = $apiResult['results'] ?? [];
                }
            } elseif ($sortable) {
                $apiUrl = 'https://api.themoviedb.org/3/discover/' . $request->type;
                $apiParams = ['sort_by' => $sortable, 'api_key' => config('settings.tmdb_api'), 'language' => config('settings.tmdb_language'), 'page' => $page];
                $response = Http::get($apiUrl, $apiParams);
                $apiResult = $response->json();
                $results = $apiResult['results'] ?? [];
            }

            if (!empty($results)) {
                foreach ($results as $item) {
                    if (isset($item['id'], $item['poster_path'])) {
                        $check = Post::where('tmdb_id', $item['id'])->where('type', $request->type)->first();
                        if (!$check) {
                            $listings[] = $this->tmdbFetchTrait($item, $request->type);
                        }
                    }
                }
            }
            $result = $apiResult;
        }
        
        $calendarData = $this->getEnrichedCalendarData();
        $recentMoviesData = $this->getRecentMoviesData();
        $recentSeriesData = $this->getRecentSeriesData();
        $firstImportData = $this->getFirstImportData();

        return view('admin.tmdb.show', array_merge(
            compact('config', 'request', 'listings', 'result'),
            $calendarData,
            $recentMoviesData,
            $recentSeriesData,
            $firstImportData
        ));
    }
    
    /**
     * Busca dados do calendário, enriquece com o status de sincronização real e formata para a view.
     *
     * @return array
     */
    private function getEnrichedCalendarData(): array
    {
        $calendarByMonth = collect();
        $calendarError = null;
        $calendarStats = ['total' => 0, 'series' => 0, 'animes' => 0, 'synchronized' => 0, 'pending' => 0];

        try {
            $response = Http::timeout(30)->get('https://superflixapi.shop/calendario.php');

            if (!$response->successful()) {
                throw new \Exception('Falha ao buscar dados do calendário. Código: ' . $response->status());
            }

            $rawCalendarData = $response->json();
            if (!is_array($rawCalendarData)) {
                throw new \Exception('A API do calendário retornou dados em um formato inesperado.');
            }

            $tmdbIds = collect($rawCalendarData)->pluck('tmdb_id')->unique()->filter()->values()->all();

            // Usar chunks para evitar problemas de memória com muitos IDs
            $localCounts = collect();
            foreach (array_chunk($tmdbIds, 100) as $chunk) {
                $chunkCounts = Post::whereIn('tmdb_id', $chunk)
                    ->where('type', 'tv')
                    ->withCount('episodes')
                    ->get()
                    ->pluck('episodes_count', 'tmdb_id');
                $localCounts = $localCounts->merge($chunkCounts);
            }

            $apiCounts = [];
            foreach ($tmdbIds as $id) {
                $apiCounts[$id] = Cache::remember('tmdb_total_episodes_' . $id, 60, function () use ($id) {
                    try {
                        $seriesData = $this->tmdbApiTrait('tv', $id);
                        $count = 0;
                        if (!empty($seriesData['seasons'])) {
                            foreach ($seriesData['seasons'] as $season) {
                                $episodes = json_decode($season['episode'], true) ?? [];
                                $count += count($episodes);
                            }
                        }
                        return $count;
                    } catch (\Exception $e) {
                        Log::error("Falha ao buscar contagem de episódios para TMDB ID {$id}: " . $e->getMessage());
                        return 0;
                    }
                });
            }

            $enrichedCalendarData = array_map(function ($item) use ($localCounts, $apiCounts, &$calendarStats) {
                $tmdbId = $item['tmdb_id'] ?? null;
                $isSynced = false;

                if ($tmdbId) {
                    $local = $localCounts->get($tmdbId, 0);
                    $api = $apiCounts[$tmdbId] ?? 0;
                    
                    $isSynced = ($api > 0) && ($local >= $api);

                    $item['local_episode_count'] = $local;
                    $item['api_episode_count'] = $api;
                }
                
                $item['local_status'] = $isSynced ? 'Sincronizado' : 'Pendente';
                
                $type = (int)($item['type'] ?? 0);
                $isAnime = ($type === 3);
                $isSeries = ($type === 2);
                
                $item['content_type'] = $isAnime ? 'anime' : ($isSeries ? 'series' : 'other');
                
                $calendarStats['total']++;
                if ($isAnime) $calendarStats['animes']++;
                if ($isSeries) $calendarStats['series']++;
                if ($isSynced) $calendarStats['synchronized']++;
                else $calendarStats['pending']++;
                
                return $item;
            }, $rawCalendarData);

            $calendarByMonth = collect($enrichedCalendarData)
                ->groupBy(fn($item) => Carbon::parse($item['air_date'])->format('Y-m'))
                ->sortKeys();

        } catch (\Exception $e) {
            $calendarError = 'Erro ao processar dados do calendário: ' . $e->getMessage();
            Log::error($calendarError);
        }

        return compact('calendarByMonth', 'calendarError', 'calendarStats');
    }

    /**
     * Busca dados de filmes recentes de uma API externa.
     *
     * @return array
     */
    private function getRecentMoviesData(): array
    {
        $recentMovies = [];
        $recentMoviesError = null;

        try {
            $response = Http::timeout(30)->get('https://superflixapi.asia/lista?category=movie&type=tmdb&format=json');
            if (!$response->successful()) {
                throw new \Exception('Falha ao buscar a lista de filmes recentes. Código: ' . $response->status());
            }
            
            $ids = $response->json();
            if (!is_array($ids)) {
                throw new \Exception('A API de filmes recentes retornou dados em um formato inesperado (não é um JSON array).');
            }

            $tmdbIds = collect($ids)
                ->map(fn($id) => is_string($id) ? trim($id) : $id)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int)$id);

            $latestTmdbIds = $tmdbIds->take(-100)->values()->all();

            if (empty($latestTmdbIds)) {
                return compact('recentMovies', 'recentMoviesError');
            }
            
            $existingMovieIds = Post::where('type', 'movie')
                ->whereIn('tmdb_id', $latestTmdbIds)
                ->pluck('tmdb_id')
                ->all();
                
            $newMovieIds = array_values(array_diff($latestTmdbIds, $existingMovieIds));

            foreach ($newMovieIds as $id) {
                // Buscar e montar dados consistentes para a view
                $details = Cache::remember('tmdb_movie_details_' . $id, 1440, function () use ($id) {
                    try {
                        return $this->tmdbApiTrait('movie', $id);
                    } catch (\Throwable $e) {
                        Log::warning("Falha ao buscar detalhes do TMDB para filme {$id}: " . $e->getMessage());
                        return null;
                    }
                });

                if (!$details || empty($details['title'])) {
                    Log::warning("Filme recente com TMDB ID {$id} foi ignorado por falta de detalhes (ex: título).");
                    continue;
                }

                $image = $details['image'] ?? null;
                if (!$image && !empty($details['poster'])) {
                    $image = 'https://image.tmdb.org/t/p/w200' . $details['poster'];
                }

                $recentMovies[] = [
                    'id' => (int)($details['tmdb_id'] ?? $id),
                    'title' => $details['title'] ?? 'Sem título',
                    'overview' => $details['overview'] ?? '',
                    'release_date' => $details['release_date'] ?? '',
                    'vote_average' => $details['vote_average'] ?? 0,
                    'image' => $image ?: '',
                ];
            }
        } catch (\Exception $e) {
            $recentMoviesError = $e->getMessage();
            Log::error("Erro ao buscar filmes recentes: " . $recentMoviesError);
        }

        return compact('recentMovies', 'recentMoviesError');
    }

    /**
     * Busca dados de séries recentes de uma API externa.
     *
     * @return array
     */
    private function getRecentSeriesData(): array
    {
        $recentSeries = [];
        $recentSeriesError = null;

        try {
            $response = Http::timeout(30)->get('https://superflixapi.asia/lista?category=serie&type=tmdb&format=json');
            if (!$response->successful()) {
                throw new \Exception('Falha ao buscar a lista de séries recentes. Código: ' . $response->status());
            }
            
            $ids = $response->json();
            if (!is_array($ids)) {
                throw new \Exception('A API de séries recentes retornou dados em um formato inesperado (não é um JSON array).');
            }

            $tmdbIds = collect($ids)
                ->map(fn($id) => is_string($id) ? trim($id) : $id)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int)$id);

            $latestTmdbIds = $tmdbIds->take(-100)->values()->all();

            if (empty($latestTmdbIds)) {
                return compact('recentSeries', 'recentSeriesError');
            }
            
            $existingSeriesIds = Post::where('type', 'tv')
                ->whereIn('tmdb_id', $latestTmdbIds)
                ->pluck('tmdb_id')
                ->all();
                
            $newSeriesIds = array_values(array_diff($latestTmdbIds, $existingSeriesIds));

            foreach ($newSeriesIds as $id) {
                // Buscar e montar dados consistentes para a view
                $details = Cache::remember('tmdb_series_details_' . $id, 1440, function () use ($id) {
                    try {
                        return $this->tmdbApiTrait('tv', $id);
                    } catch (\Throwable $e) {
                        Log::warning("Falha ao buscar detalhes do TMDB para série {$id}: " . $e->getMessage());
                        return null;
                    }
                });

                if (!$details || empty($details['title'])) {
                    Log::warning("Série recente com TMDB ID {$id} foi ignorada por falta de detalhes (ex: título).");
                    continue;
                }

                $image = $details['image'] ?? null;
                if (!$image && !empty($details['poster'])) {
                    $image = 'https://image.tmdb.org/t/p/w200' . $details['poster'];
                }

                $recentSeries[] = [
                    'id' => (int)($details['tmdb_id'] ?? $id),
                    'title' => $details['title'] ?? 'Sem título',
                    'overview' => $details['overview'] ?? '',
                    'release_date' => $details['release_date'] ?? '',
                    'vote_average' => $details['vote_average'] ?? 0,
                    'image' => $image ?: '',
                ];
            }
        } catch (\Exception $e) {
            $recentSeriesError = $e->getMessage();
            Log::error("Erro ao buscar séries recentes: " . $recentSeriesError);
        }

        return compact('recentSeries', 'recentSeriesError');
    }

    /**
     * Busca dados de filmes e séries para primeira importação com limite de memória.
     * Limita o número de itens e usa processamento em chunks para evitar estouro de memória.
     *
     * @return array
     */
    private function getFirstImportData(): array
    {
        $firstImportMovies = [];
        $firstImportSeries = [];
        $firstImportError = null;
        
        // Limitar a 500 itens por tipo para evitar problemas de memória
        $maxItemsPerType = 500;
        $chunkSize = 100; // Processar IDs em chunks de 100

        try {
            // Buscar filmes
            $movieResponse = Http::timeout(30)->get('https://superflixapi.asia/lista?category=movie&type=tmdb&format=json');
            if ($movieResponse->successful()) {
                $movieIds = $movieResponse->json();
                if (is_array($movieIds)) {
                    $tmdbMovieIds = collect($movieIds)
                        ->map(fn($id) => is_string($id) ? trim($id) : $id)
                        ->filter(fn($id) => is_numeric($id))
                        ->map(fn($id) => (int)$id)
                        ->take($maxItemsPerType); // Limitar a quantidade de IDs

                    $allMovieIds = $tmdbMovieIds->values()->all();

                    if (!empty($allMovieIds)) {
                        // Processar IDs em chunks para evitar queries muito grandes
                        $existingMovieIds = [];
                        foreach (array_chunk($allMovieIds, $chunkSize) as $chunk) {
                            $chunkExisting = Post::where('type', 'movie')
                                ->whereIn('tmdb_id', $chunk)
                                ->pluck('tmdb_id')
                                ->all();
                            $existingMovieIds = array_merge($existingMovieIds, $chunkExisting);
                        }
                            
                        $newMovieIds = array_values(array_diff($allMovieIds, $existingMovieIds));

                        foreach ($newMovieIds as $id) {
                            // Para evitar timeout do Cloudflare na primeira carga da página,
                            // não fazemos chamadas à API TMDB aqui. Apenas retornamos os IDs.
                            $firstImportMovies[] = [
                                'id' => (int)$id,
                                'title' => 'ID: ' . $id, // Título temporário apenas com o ID
                                'overview' => '',
                                'release_date' => '',
                                'vote_average' => 0,
                                'image' => '',
                            ];
                        }
                    }
                }
            }

            // Buscar séries
            $seriesResponse = Http::timeout(30)->get('https://superflixapi.asia/lista?category=serie&type=tmdb&format=json');
            if ($seriesResponse->successful()) {
                $seriesIds = $seriesResponse->json();
                if (is_array($seriesIds)) {
                    $tmdbSeriesIds = collect($seriesIds)
                        ->map(fn($id) => is_string($id) ? trim($id) : $id)
                        ->filter(fn($id) => is_numeric($id))
                        ->map(fn($id) => (int)$id)
                        ->take($maxItemsPerType); // Limitar a quantidade de IDs

                    $allSeriesIds = $tmdbSeriesIds->values()->all();

                    if (!empty($allSeriesIds)) {
                        // Processar IDs em chunks para evitar queries muito grandes
                        $existingSeriesIds = [];
                        foreach (array_chunk($allSeriesIds, $chunkSize) as $chunk) {
                            $chunkExisting = Post::where('type', 'tv')
                                ->whereIn('tmdb_id', $chunk)
                                ->pluck('tmdb_id')
                                ->all();
                            $existingSeriesIds = array_merge($existingSeriesIds, $chunkExisting);
                        }
                            
                        $newSeriesIds = array_values(array_diff($allSeriesIds, $existingSeriesIds));

                        foreach ($newSeriesIds as $id) {
                            // Para evitar timeout do Cloudflare na primeira carga da página,
                            // não fazemos chamadas à API TMDB aqui. Apenas retornamos os IDs.
                            $firstImportSeries[] = [
                                'id' => (int)$id,
                                'title' => 'ID: ' . $id, // Título temporário apenas com o ID
                                'overview' => '',
                                'release_date' => '',
                                'vote_average' => 0,
                                'image' => '',
                            ];
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $firstImportError = $e->getMessage();
            Log::error("Erro ao buscar dados para primeira importação: " . $firstImportError);
        }

        return compact('firstImportMovies', 'firstImportSeries', 'firstImportError');
    }

    public function settings(Request $request)
    {
        $config = ['title' => __('Tool'), 'nav' => 'tool'];
        return view('admin.tmdb.settings', compact('config', 'request'));
    }

    public function update(Request $request)
    {
        $save_data = ['tmdb_api', 'tmdb_language', 'tmdb_people_limit', 'tmdb_image', 'draft_post', 'import_season', 'import_episode', 'vidsrc'];
        foreach ($save_data as $item) {
            update_settings($item, $request->$item);
        }
        Cache::flush();
        return redirect()->route('admin.tmdb.settings')->with('success', __(':title has been updated', ['title' => 'Tool']));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'tmdb_id' => 'required|integer',
                'type' => 'required|in:tv,movie',
            ]);

            $postArray = $this->tmdbApiTrait($request->type, $request->tmdb_id);
            if (empty($postArray)) {
                return response()->json(['message' => 'Não foi possível obter dados do TMDB.'], 404);
            }
            
            unset($postArray['tags']);
            $tags = [];
            if (!empty($postArray['title'])) {
                $tags[] = lang('assistir') . ' ' . $postArray['title'];
                $tags[] = lang('onde') . ' ' . lang('assistir') . ' ' . $postArray['title'];
                $tags[] = lang('assistir') . ' ' . lang('online') . ' ' . $postArray['title'];
                $tags[] = $postArray['title'] . ' ' . lang('online');
            }
            if (!empty($postArray['title_sub'])) {
                $tags[] = lang('ver') . ' ' . $postArray['title_sub'];
                $tags[] = lang('ver') . ' ' . $postArray['title_sub'] . ' ' . lang('online');
            }
            if (!empty($postArray['release_date'])) {
                $year = date('Y', strtotime($postArray['release_date']));
                $tags[] = lang('assistir') . ' ' . $postArray['title'] . ' ' . $year;
            }
            $postArray['tags'] = $tags;

            $tmdb_id = $postArray['tmdb_id'];
            $existingPost = Post::where('tmdb_id', $tmdb_id)->where('type', $request->type)->first();
            
            $isUpdate = false;
            if ($existingPost) {
                if ($request->type === 'movie') {
                    return response()->json(['message' => "Filme '{$postArray['title']}' já existe, ignorado."], 208);
                }
                
                $isUpdate = true;
                $apiEpisodeCount = 0;
                if (!empty($postArray['seasons'])) {
                    foreach ($postArray['seasons'] as $season) {
                        $episodes = json_decode($season['episode'], true) ?? [];
                        $apiEpisodeCount += count($episodes);
                    }
                }
                $dbEpisodeCount = $existingPost->episodes()->count();

                if ($apiEpisodeCount > 0 && $dbEpisodeCount >= $apiEpisodeCount) {
                    return response()->json(['message' => "Série '{$postArray['title']}' já está atualizada, ignorada."], 208);
                }
                
                $model = $existingPost;
                DB::transaction(function () use ($model) {
                    $model->seasons()->delete();
                    $model->episodes()->delete();
                });
            } else {
                $model = new Post();
            }

            DB::transaction(function () use ($model, $postArray, $request) {
                $folderDate = $model->exists ? $model->created_at->format('m-Y') . '/' : date('m-Y') . '/';

                if (config('settings.tmdb_image') != 'active' && !$model->exists) {
                    if (isset($postArray['image'])) {
                        $imagename = Str::random(10);
                        $model->image = fileUpload($postArray['image'], config('attr.poster.path') . $folderDate, config('attr.poster.size_x'), config('attr.poster.size_y'), $imagename, 'webp');
                    }
                    if (isset($postArray['cover'])) {
                        $imagename = Str::random(10);
                        $model->cover = fileUpload($postArray['cover'], config('attr.poster.path') . $folderDate, config('attr.poster.cover_size_x'), config('attr.poster.cover_size_y'), 'cover-' . $imagename, 'webp');
                    }
                }

                $model->type = $postArray['type'];
                $model->title = $postArray['title'];
                $model->title_sub = $postArray['title_sub'];
                $model->tagline = $postArray['tagline'];
                $model->overview = $postArray['overview'];
                $model->release_date = $postArray['release_date'];
                $model->runtime = $postArray['runtime'];
                $model->vote_average = $postArray['vote_average'];
                $model->country_id = $postArray['country_id'];
                $model->trailer = $postArray['trailer'];
                $model->tmdb_image = $postArray['tmdb_image'];
                $model->imdb_id = $postArray['imdb_id'];
                $model->tmdb_id = $postArray['tmdb_id'];
                $model->status = config('settings.draft_post') == 'active' ? 'draft' : 'publish';
                $model->save();

                if (isset($postArray['genres'])) {
                    $model->genres()->sync(collect($postArray['genres'])->pluck('current_id'));
                }

                if (isset($postArray['tags'])) {
                    $tagArray = [];
                    foreach (array_unique($postArray['tags']) as $tag) {
                        if ($tag) {
                            $tagComponent = Tag::firstOrCreate(['tag' => $tag, 'type' => 'post']);
                            $tagArray[] = $tagComponent->id;
                        }
                    }
                    $model->tags()->sync($tagArray);
                }

                if (isset($postArray['peoples'])) {
                    $syncPeople = [];
                    foreach ($postArray['peoples'] as $people) {
                        $traitPeople = $this->PeopleTmdb($people);
                        if (!empty($traitPeople->id)) $syncPeople[] = $traitPeople->id;
                    }
                    $model->peoples()->sync($syncPeople);
                }

                if (isset($postArray['seasons'])) {
                    foreach ($postArray['seasons'] as $seasonData) {
                        if (!empty($seasonData['season_number'])) {
                            $season = $model->seasons()->create([
                                'name' => $seasonData['name'],
                                'season_number' => $seasonData['season_number'],
                            ]);
                            
                            $episodes = json_decode($seasonData['episode'], true) ?: [];
                            foreach ($episodes as $episodeKey) {
                                $episode = new PostEpisode();
                                $episode->post_id = $model->id;
                                
                                if (config('settings.tmdb_image') != 'active' && isset($episodeKey['image'])) {
                                    $imagename = Str::random(10);
                                    $episode->image = fileUpload($episodeKey['image'], config('attr.poster.episode_path') . $folderDate, config('attr.poster.episode_size_x'), config('attr.poster.episode_size_y'), $imagename, 'webp');
                                }
                                
                                $episode->name = $episodeKey['name'] ?? '';
                                $episode->season_number = $season->season_number;
                                $episode->episode_number = $episodeKey['episode_number'] ?? 0;
                                $episode->overview = $episodeKey['overview'] ?? '';
                                $episode->tmdb_image = $episodeKey['tmdb_image'] ?? null;
                                $episode->runtime = $episodeKey['runtime'] ?? null;
                                $episode->status = config('settings.draft_post') == 'active' ? 'draft' : 'publish';
                                $season->episodes()->save($episode);
                            }
                        }
                    }
                }
            });

            $message = $isUpdate ? __(':title updated', ['title' => "'{$postArray['title']}'"]) : __(':title created', ['title' => "'{$postArray['title']}'"]);
            return response()->json(['message' => $message], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro: ' . $e->getMessage() . ' na linha ' . $e->getLine()], 500);
        }
    }

    public function tmdbSingleFetch(Request $request)
    {
        $postArray = $this->tmdbApiTrait($request->type, $request->tmdb_id);

        if (empty($postArray)) {
            return response()->json(['message' => "Não foi possível encontrar dados para o ID informado."], 404);
        }

        unset($postArray['tags']);
        $tags = [];
        if (!empty($postArray['title'])) {
            $tags[] = lang('assistir') . ' ' . $postArray['title'];
            $tags[] = lang('onde') . ' ' . lang('assistir') . ' ' . $postArray['title'];
            $tags[] = $postArray['title'] . ' ' . lang('online');
            $tags[] = $postArray['title'] . ' ' . lang('completo') . ' ' . lang('dublado');
            $tags[] = $postArray['title'] . ' ' . lang('gratis');
            $tags[] = lang('filme') . ' ' . lang('completo') . ' ' . $postArray['title'];
        }
        if (!empty($postArray['title_sub'])) {
            $tags[] = lang('ver') . ' ' . $postArray['title_sub'];
            $tags[] = lang('ver') . ' ' . $postArray['title_sub'] . ' ' . lang('online');
        }
        if (!empty($postArray['release_date'])) {
            $year = date('Y', strtotime($postArray['release_date']));
            $tags[] = lang('assistir') . ' ' . $postArray['title'] . ' ' . $year;
        }
        $postArray['tags'] = $tags;

        if (!empty($postArray['title_sub'])) {
            $postArray['title_sub'] = lang('ver') . ' ' . $postArray['title_sub'] . ' ' . lang('online');
        }
        if (!empty($postArray['overview'])) {
            $postArray['overview'] = $postArray['title'] . ' ' . $postArray['overview'] . ' ' . lang('ver') . ' ' . lang('filme') . ' ' . lang('online');
        }

        return response()->json($postArray);
    }

    public function tmdbEpisodeFetch(Request $request)
    {
        $postArray = $this->tmdbEpisodeApiTrait($request);
        return response()->json($postArray);
    }
    
    /**
     * Método para ser chamado via CRON Job para sincronizar todos os itens pendentes do calendário.
     */
    public function cronSyncAllPending($key)
    {
        $secretKey = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
        if ($key !== $secretKey) {
            return response('Acesso não autorizado.', 403);
        }

        set_time_limit(3600); 

        $calendarData = $this->getEnrichedCalendarData();
        $items = $calendarData['calendarByMonth']->flatten(1);
        
        $pendingItems = $items->filter(fn($item) => ($item['local_status'] ?? 'Pendente') === 'Pendente')->unique('tmdb_id');

        if ($pendingItems->isEmpty()) {
            return response('Nenhum item pendente para sincronizar.');
        }
        
        $created = 0; $updated = 0; $failed = 0; $skipped = 0;

        foreach ($pendingItems as $item) {
            try {
                $request = new Request(['tmdb_id' => $item['tmdb_id'], 'type' => 'tv']);
                $response = $this->store($request);
                $status = $response->getStatusCode();

                if ($status === 200) {
                    if (str_contains(optional($response->getData())->message, 'updated')) $updated++; else $created++;
                } elseif ($status === 208) {
                    $skipped++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("CRON Sync (Calendário) Falhou para TMDB ID {$item['tmdb_id']}: " . $e->getMessage());
                $failed++;
            }
        }
        
        $summary = "Sincronização (Calendário) via CRON concluída. Criados: {$created}, Atualizados: {$updated}, Ignorados: {$skipped}, Falhas: {$failed}.";
        Log::info($summary);
        
        return response($summary);
    }

    /**
     * Método para ser chamado via CRON Job para sincronizar os filmes recentes.
     */
    public function cronSyncRecentMovies($key)
    {
        $secretKey = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
        if ($key !== $secretKey) {
            return response('Acesso não autorizado.', 403);
        }

        set_time_limit(3600);

        try {
            $recentMoviesData = $this->getRecentMoviesData();
            $newMovieIds = collect($recentMoviesData['recentMovies'])->pluck('id')->all();

            if (empty($newMovieIds)) {
                return response('Nenhum filme novo para sincronizar.');
            }

            $created = 0; $failed = 0; $skipped = 0;

            foreach ($newMovieIds as $tmdbId) {
                try {
                    $request = new Request(['tmdb_id' => $tmdbId, 'type' => 'movie']);
                    $storeResponse = $this->store($request);
                    $status = $storeResponse->getStatusCode();

                    if ($status === 200) $created++;
                    elseif ($status === 208) $skipped++;
                    else $failed++;
                } catch (\Exception $e) {
                    Log::error("CRON Sync (Filmes) Falhou para TMDB ID {$tmdbId}: " . $e->getMessage());
                    $failed++;
                }
            }
            
            $summary = "Sincronização de filmes via CRON concluída. Criados: {$created}, Ignorados: {$skipped}, Falhas: {$failed}.";
            Log::info($summary);
            
            return response($summary);

        } catch (\Exception $e) {
            $errorMsg = "Erro no CRON de filmes recentes: " . $e->getMessage();
            Log::error($errorMsg);
            return response($errorMsg, 500);
        }
    }

    /**
     * Método para ser chamado via CRON Job para sincronizar as séries recentes.
     */
    public function cronSyncRecentSeries($key)
    {
        $secretKey = env('CRON_SYNC_KEY', 'SUA_CHAVE_SECRETA_PADRAO');
        if ($key !== $secretKey) {
            return response('Acesso não autorizado.', 403);
        }

        set_time_limit(3600);

        try {
            $recentSeriesData = $this->getRecentSeriesData();
            $newSeriesIds = collect($recentSeriesData['recentSeries'])->pluck('id')->all();

            if (empty($newSeriesIds)) {
                return response('Nenhuma série nova para sincronizar.');
            }

            $created = 0; $failed = 0; $skipped = 0;

            foreach ($newSeriesIds as $tmdbId) {
                try {
                    $request = new Request(['tmdb_id' => $tmdbId, 'type' => 'tv']);
                    $storeResponse = $this->store($request);
                    $status = $storeResponse->getStatusCode();

                    if ($status === 200) $created++;
                    elseif ($status === 208) $skipped++;
                    else $failed++;
                } catch (\Exception $e) {
                    Log::error("CRON Sync (Séries) Falhou para TMDB ID {$tmdbId}: " . $e->getMessage());
                    $failed++;
                }
            }
            
            $summary = "Sincronização de séries via CRON concluída. Criados: {$created}, Ignorados: {$skipped}, Falhas: {$failed}.";
            Log::info($summary);
            
            return response($summary);

        } catch (\Exception $e) {
            $errorMsg = "Erro no CRON de séries recentes: " . $e->getMessage();
            Log::error($errorMsg);
            return response($errorMsg, 500);
        }
    }
}
