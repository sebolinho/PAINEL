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

    public function show(Request $request)
    {
        $config = [
            'title' => __('Tool'),
            'nav' => 'tool',
        ];
        if (!config('settings.tmdb_api') || !config('settings.tmdb_language')) {
            return redirect()->route('admin.tmdb.settings');
        }

        // Calendário
        $calendarByMonth = collect();
        $calendarError = null;
        $calendarStats = ['total' => 0, 'series' => 0, 'animes' => 0, 'synchronized' => 0, 'pending' => 0];

        try {
            $response = Http::timeout(30)->get('https://superflixapi.shop/calendario.php');

            if ($response->successful()) {
                $rawCalendarData = $response->json();
                if (is_array($rawCalendarData)) {
                    $tmdbIds = collect($rawCalendarData)->pluck('tmdb_id')->unique()->filter()->all();

                    // Obter todos os episódios locais para verificação completa de sincronização
                    $localEpisodes = PostEpisode::query()
                        ->join('posts', 'post_episodes.post_id', '=', 'posts.id')
                        ->whereIn('posts.tmdb_id', $tmdbIds)
                        ->where('posts.type', 'tv')
                        ->get(['posts.tmdb_id', 'post_episodes.season_number', 'post_episodes.episode_number'])
                        ->keyBy(fn($item) => $item->tmdb_id . '-' . $item->season_number . '-' . $item->episode_number);

                    // Agrupar episódios da API por TMDB ID e temporada para verificação completa
                    $apiEpisodesBySeries = collect($rawCalendarData)->groupBy('tmdb_id');
                    
                    // Verificar sincronização completa por série/temporada
                    $seriesSync = [];
                    foreach ($apiEpisodesBySeries as $tmdbId => $episodes) {
                        $seasonGroups = collect($episodes)->groupBy(fn($ep) => $ep['season'] ?? $ep['season_number'] ?? 0);
                        
                        foreach ($seasonGroups as $seasonNumber => $seasonEpisodes) {
                            // Verificar se TODOS os episódios desta temporada existem no banco
                            $allEpisodesExist = true;
                            foreach ($seasonEpisodes as $episode) {
                                $episodeNumber = $episode['number'] ?? $episode['episode_number'] ?? null;
                                $key = $tmdbId . '-' . $seasonNumber . '-' . $episodeNumber;
                                if (!$localEpisodes->has($key)) {
                                    $allEpisodesExist = false;
                                    break;
                                }
                            }
                            
                            $seriesSync[$tmdbId][$seasonNumber] = $allEpisodesExist;
                        }
                    }

                    $enrichedCalendarData = array_map(function ($item) use ($localEpisodes, $seriesSync, &$calendarStats) {
                        $seasonNumber = $item['season'] ?? $item['season_number'] ?? null;
                        $episodeNumber = $item['number'] ?? $item['episode_number'] ?? null;
                        $tmdbId = $item['tmdb_id'] ?? null;
                        $key = $tmdbId . '-' . $seasonNumber . '-' . $episodeNumber;
                        
                        // Verificação individual do episódio
                        $episodeExists = ($tmdbId && $localEpisodes->has($key));
                        
                        // Verificação se a série/temporada está completamente sincronizada
                        $seasonFullySynced = isset($seriesSync[$tmdbId][$seasonNumber]) ? $seriesSync[$tmdbId][$seasonNumber] : false;
                        
                        // Marcar como sincronizado APENAS se a temporada inteira estiver sincronizada
                        $item['local_status'] = $seasonFullySynced ? 'Sincronizado' : 'Pendente';
                        $item['episode_exists'] = $episodeExists; // Informação adicional para debug
                        $item['debug_info'] = "TMDB: {$tmdbId}, T{$seasonNumber}E{$episodeNumber}, Status: " . ($seasonFullySynced ? 'Sincronizado' : 'Pendente');
                        
                        // Tipos: series=2, anime=3
                        $type = (int)($item['type'] ?? 0);
                        $isAnime = ($type === 3);
                        $isSeries = ($type === 2);
                        
                        $item['content_type'] = $isAnime ? 'anime' : ($isSeries ? 'series' : 'other');
                        
                        // Estatísticas
                        $calendarStats['total']++;
                        if ($isAnime) {
                            $calendarStats['animes']++;
                        }
                        if ($isSeries) {
                            $calendarStats['series']++;
                        }
                        if ($seasonFullySynced) {
                            $calendarStats['synchronized']++;
                        } else {
                            $calendarStats['pending']++;
                        }
                        
                        return $item;
                    }, $rawCalendarData);

                    // Agrupa por mês
                    $calendarByMonth = collect($enrichedCalendarData)
                        ->groupBy(fn($item) => Carbon::parse($item['air_date'])->format('Y-m'))
                        ->sortKeys();

                } else {
                    $calendarError = 'A API do calendário retornou dados em um formato inesperado.';
                }
            } else {
                $calendarError = 'Falha ao buscar dados do calendário. Código: ' . $response->status();
            }
        } catch (\Exception $e) {
            $calendarError = 'Erro ao conectar com a API do calendário: ' . $e->getMessage();
            Log::error($calendarError);
        }

        return view('admin.tmdb.show', compact('config', 'request', 'calendarByMonth', 'calendarError', 'calendarStats'));
    }

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
        
        // Carregar calendário também nesta página
        $calendarByMonth = collect();
        $calendarError = null;
        $calendarStats = ['total' => 0, 'series' => 0, 'animes' => 0, 'synchronized' => 0, 'pending' => 0];
        
        try {
            $response = Http::timeout(30)->get('https://superflixapi.shop/calendario.php');
            if ($response->successful()) {
                $rawCalendarData = $response->json();
                if (is_array($rawCalendarData)) {
                    $tmdbIds = collect($rawCalendarData)->pluck('tmdb_id')->unique()->filter()->all();
                    
                    // Obter todos os episódios locais para verificação completa de sincronização
                    $localEpisodes = PostEpisode::query()
                        ->join('posts', 'post_episodes.post_id', '=', 'posts.id')
                        ->whereIn('posts.tmdb_id', $tmdbIds)
                        ->where('posts.type', 'tv')
                        ->get(['posts.tmdb_id', 'post_episodes.season_number', 'post_episodes.episode_number'])
                        ->keyBy(fn($item) => $item->tmdb_id . '-' . $item->season_number . '-' . $item->episode_number);

                    // Agrupar episódios da API por TMDB ID e temporada para verificação completa
                    $apiEpisodesBySeries = collect($rawCalendarData)->groupBy('tmdb_id');
                    
                    // Verificar sincronização completa por série/temporada
                    $seriesSync = [];
                    foreach ($apiEpisodesBySeries as $tmdbId => $episodes) {
                        $seasonGroups = collect($episodes)->groupBy(fn($ep) => $ep['season'] ?? $ep['season_number'] ?? 0);
                        
                        foreach ($seasonGroups as $seasonNumber => $seasonEpisodes) {
                            // Verificar se TODOS os episódios desta temporada existem no banco
                            $allEpisodesExist = true;
                            foreach ($seasonEpisodes as $episode) {
                                $episodeNumber = $episode['number'] ?? $episode['episode_number'] ?? null;
                                $key = $tmdbId . '-' . $seasonNumber . '-' . $episodeNumber;
                                if (!$localEpisodes->has($key)) {
                                    $allEpisodesExist = false;
                                    break;
                                }
                            }
                            
                            $seriesSync[$tmdbId][$seasonNumber] = $allEpisodesExist;
                        }
                    }

                    $enrichedCalendarData = array_map(function ($item) use ($localEpisodes, $seriesSync, &$calendarStats) {
                        $seasonNumber = $item['season'] ?? $item['season_number'] ?? null;
                        $episodeNumber = $item['number'] ?? $item['episode_number'] ?? null;
                        $tmdbId = $item['tmdb_id'] ?? null;
                        $key = $tmdbId . '-' . $seasonNumber . '-' . $episodeNumber;
                        
                        // Verificação individual do episódio
                        $episodeExists = ($tmdbId && $localEpisodes->has($key));
                        
                        // Verificação se a série/temporada está completamente sincronizada
                        $seasonFullySynced = isset($seriesSync[$tmdbId][$seasonNumber]) ? $seriesSync[$tmdbId][$seasonNumber] : false;
                        
                        // Marcar como sincronizado APENAS se a temporada inteira estiver sincronizada
                        $item['local_status'] = $seasonFullySynced ? 'Sincronizado' : 'Pendente';
                        $item['episode_exists'] = $episodeExists; // Informação adicional para debug
                        $item['debug_info'] = "TMDB: {$tmdbId}, T{$seasonNumber}E{$episodeNumber}, Status: " . ($seasonFullySynced ? 'Sincronizado' : 'Pendente');
                        
                        $type = (int)($item['type'] ?? 0);
                        $isAnime = ($type === 3);
                        $isSeries = ($type === 2);
                        
                        $item['content_type'] = $isAnime ? 'anime' : ($isSeries ? 'series' : 'other');
                        
                        $calendarStats['total']++;
                        if ($isAnime) $calendarStats['animes']++;
                        if ($isSeries) $calendarStats['series']++;
                        if ($seasonFullySynced) $calendarStats['synchronized']++;
                        else $calendarStats['pending']++;
                        
                        return $item;
                    }, $rawCalendarData);

                    $calendarByMonth = collect($enrichedCalendarData)
                        ->groupBy(fn($item) => Carbon::parse($item['air_date'])->format('Y-m'))
                        ->sortKeys();
                } else {
                    $calendarError = 'A API do calendário retornou dados em um formato inesperado.';
                }
            } else {
                $calendarError = 'Falha ao buscar dados do calendário. Código: ' . $response->status();
            }
        } catch (\Exception $e) {
            $calendarError = 'Erro ao conectar com a API do calendário: ' . $e->getMessage();
            Log::error($calendarError);
        }

        return view('admin.tmdb.show', compact('config', 'request', 'listings', 'result', 'calendarByMonth', 'calendarError', 'calendarStats'));
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
                $tags[] = 'assistir ' . $postArray['title'];
                $tags[] = 'onde assistir ' . $postArray['title'];
                $tags[] = 'assistir online' . $postArray['title'];
                $tags[] = $postArray['title'] . ' online';
            }
            if (!empty($postArray['title_sub'])) {
                $tags[] = 'Ver ' . $postArray['title_sub'];
                $tags[] = 'Ver ' . $postArray['title_sub'] . ' online';
            }
            if (!empty($postArray['release_date'])) {
                $year = date('Y', strtotime($postArray['release_date']));
                $tags[] = 'assistir ' . $postArray['title'] . ' ' . $year;
            }
            $postArray['tags'] = $tags;

            $tmdb_id = $postArray['tmdb_id'];
            $existingPost = Post::where('tmdb_id', $tmdb_id)->where('type', $request->type)->first();
            
            $isUpdate = false;
            if ($existingPost) {
                if ($request->type === 'movie') {
                    return response()->json(['message' => 'Filme já existe, ignorado.'], 208);
                }
                
                $isUpdate = true;
                $apiSeasonCount = count($postArray['seasons'] ?? []);
                $dbSeasonCount = $existingPost->seasons()->count();
                
                // Calcular número total de episódios da API
                $apiEpisodeCount = 0;
                $apiEpisodesBySeasonAndNumber = [];
                foreach ($postArray['seasons'] ?? [] as $seasonData) {
                    $seasonNumber = $seasonData['season_number'] ?? 0;
                    $episodes = json_decode($seasonData['episode'], true) ?? [];
                    $apiEpisodeCount += count($episodes);
                    
                    // Mapear episódios por temporada e número para verificação precisa
                    foreach ($episodes as $episode) {
                        $episodeNumber = $episode['episode_number'] ?? 0;
                        $apiEpisodesBySeasonAndNumber["{$seasonNumber}-{$episodeNumber}"] = true;
                    }
                }
                
                // Calcular número de episódios no banco
                $dbEpisodeCount = $existingPost->episodes()->count();
                
                // Verificar se todos os episódios da API já existem no banco
                $dbEpisodesBySeasonAndNumber = [];
                $existingEpisodes = $existingPost->episodes()->get(['season_number', 'episode_number']);
                foreach ($existingEpisodes as $episode) {
                    $dbEpisodesBySeasonAndNumber["{$episode->season_number}-{$episode->episode_number}"] = true;
                }
                
                // Verificar se todos os episódios da API existem no banco
                $allApiEpisodesExist = true;
                foreach (array_keys($apiEpisodesBySeasonAndNumber) as $episodeKey) {
                    if (!isset($dbEpisodesBySeasonAndNumber[$episodeKey])) {
                        $allApiEpisodesExist = false;
                        break;
                    }
                }

                if ($apiSeasonCount <= $dbSeasonCount && $allApiEpisodesExist) {
                    return response()->json(['message' => 'Série já está atualizada, ignorada.'], 208);
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

            $message = $isUpdate ? __(':title updated', ['title' => $postArray['title']]) : __(':title created', ['title' => $postArray['title']]);
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
            $tags[] = 'assistir ' . $postArray['title'];
            $tags[] = 'onde assistir ' . $postArray['title'];
            $tags[] = $postArray['title'] . ' online';
            $tags[] = $postArray['title'] . ' completo dublado';
            $tags[] = $postArray['title'] . ' grátis';
            $tags[] = 'filme completo ' . $postArray['title'];
        }
        if (!empty($postArray['title_sub'])) {
            $tags[] = 'Ver ' . $postArray['title_sub'];
            $tags[] = 'Ver ' . $postArray['title_sub'] . ' online';
        }
        if (!empty($postArray['release_date'])) {
            $year = date('Y', strtotime($postArray['release_date']));
            $tags[] = 'assistir ' . $postArray['title'] . ' ' . $year;
        }
        $postArray['tags'] = $tags;

        if (!empty($postArray['title_sub'])) {
            $postArray['title_sub'] = 'Ver ' . $postArray['title_sub'] . ' online';
        }
        if (!empty($postArray['overview'])) {
            $postArray['overview'] = $postArray['title'] . ' ' . $postArray['overview'] . ' ver filme online';
        }

        return response()->json($postArray);
    }

    public function tmdbEpisodeFetch(Request $request)
    {
        $postArray = $this->tmdbEpisodeApiTrait($request);
        return response()->json($postArray);
    }
}