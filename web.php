<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TmdbController;

if (config('settings.language')) {
    App::setLocale(config('settings.language'));
} else { // This is optional as Laravel will automatically set the fallback language if there is none specified
    App::setLocale('es');
}
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
if (config('settings.landing') == 'active') {
    Route::get('/', [\App\Http\Controllers\IndexController::class, 'landing'])->name('landing');
    Route::post('/', [\App\Http\Controllers\IndexController::class, 'search'])->name('landing');
    Route::get('/inicio', [\App\Http\Controllers\IndexController::class, 'index'])->name('index');
} else {
    Route::get('/', [\App\Http\Controllers\IndexController::class, 'index'])->name('index');
}

// Rutas para Cron Jobs de Sincronización
Route::get('/cron/sync-all-pending/{key}', [TmdbController::class, 'cronSyncAllPending'])->name('cron.sync.all');
Route::get('/cron/sync-recent-movies/{key}', [TmdbController::class, 'cronSyncRecentMovies'])->name('cron.sync.movies');
Route::get('/cron/sync-recent-series/{key}', [TmdbController::class, 'cronSyncRecentSeries'])->name('cron.sync.series');


// Navegar
Route::get(__('explorar'), [App\Http\Controllers\BrowseController::class, 'index'])->name('browse');
Route::post(__('explorar'), [App\Http\Controllers\BrowseController::class, 'index'])->name('browse');
Route::get('/robots.txt', function () {
    // Obtiene la URL actual (con protocolo http o https)
    $siteUrl = (request()->secure() ? 'https' : 'http') . '://' . request()->getHost();

    // Define el contenido del robots.txt
    $robotsContent = "User-agent: *
Allow: /
Disallow: /login
Disallow: /actores
Disallow: /colecciones
Disallow: /settings
Disallow: /perfil/
Disallow: /actor
Disallow: /actor/
Disallow: /perfil
Disallow: /pagina/
Disallow: /lang/
Disallow: /solicitar
Disallow: /pais/
Disallow: /forgot-password

Sitemap: {$siteUrl}/sitemap.xml
Sitemap: {$siteUrl}/sitemap_post_1.xml
    ";

    // Retorna el contenido con el tipo de respuesta como 'text/plain'
    return response($robotsContent)->header('Content-Type', 'text/plain');
});

// Top IMDB
Route::get(__('mejor-valoradas'), [App\Http\Controllers\BrowseController::class, 'index'])->name('topimdb');

Route::post('/tmdb/bulk-store', [TmdbController::class, 'bulkStore'])->name('admin.tmdb.bulk.store');

// Películas
Route::get(__('peliculas'), [App\Http\Controllers\BrowseController::class, 'index'])->name('movies');

// Anime
Route::get(__('anime'), [App\Http\Controllers\BrowseController::class, 'index'])->name('anime');

// Series de TV
Route::get(__('series'), [App\Http\Controllers\BrowseController::class, 'index'])->name('tvshows');

// Transmisiones en Vivo
Route::get(__('canales-en-vivo'), [App\Http\Controllers\BrowseController::class, 'broadcasts'])->name('broadcasts');

// Tendencias
Route::get(__('lo-mas-visto'), [App\Http\Controllers\BrowseController::class, 'index'])->name('trending');

// Género
Route::get(__('genero') . '/{genre}', [App\Http\Controllers\BrowseController::class, 'index'])->name('genre');

// País
Route::get(__('pais') . '/{country}', [App\Http\Controllers\BrowseController::class, 'index'])->name('country');

// Buscar
Route::get(__('buscar') . '/{search}', [App\Http\Controllers\BrowseController::class, 'index'])->name('search');

// Tag
Route::get(__('tag') . '/{tag}', [App\Http\Controllers\BrowseController::class, 'tag'])->name('tag');

// Encontrar Ahora
Route::get(__('encontrar-ahora'), [App\Http\Controllers\BrowseController::class, 'find'])->name('browse.find');

// Personas
Route::get(__('personas'), [App\Http\Controllers\BrowseController::class, 'community'])->name('peoples');

// Solicitar
Route::get(__('solicitar'), [App\Http\Controllers\BrowseController::class, 'request'])->name('request');
Route::post(__('solicitar'), [App\Http\Controllers\BrowseController::class, 'requestPost'])->name('requestPost');


Route::get(__('pelicula') . '/{slug}', [App\Http\Controllers\WatchController::class, 'movie'])->name('movie');
// Ruta para el episodio (más específica)
Route::get(__('serie') . '/ver-{slug}-temporada-{season}-episodio-{episode}', [App\Http\Controllers\WatchController::class, 'episode'])
    ->where([
        'slug' => '[a-z0-9\-]+',
        'season' => '[0-9]+',
        'episode' => '[0-9]+',
    ])
    ->name('episode');

// Ruta para la serie (más genérica)
Route::get(__('serie') . '/ver-{slug}', [App\Http\Controllers\WatchController::class, 'tv'])->name('tv');



Route::get(__('canales-en-vivo') . '/{slug}', [App\Http\Controllers\WatchController::class, 'broadcast'])->name('broadcast');

Route::get(__('embed') . '/{id}', [App\Http\Controllers\WatchController::class, 'embed'])->name('embed')->middleware('hotlink');

// Usuario
Route::get(__('perfil') . '/{username}/favoritos', [App\Http\Controllers\UserController::class, 'liked'])->name('profile.liked');
Route::get(__('perfil') . '/{username}/mi-lista', [App\Http\Controllers\UserController::class, 'watchlist'])->middleware(['auth'])->name('profile.watchlist');
Route::get(__('perfil') . '/{username}/comunidad', [App\Http\Controllers\UserController::class, 'community'])->name('profile.community');
Route::get(__('perfil') . '/{username}/comentarios', [App\Http\Controllers\UserController::class, 'comments'])->name('profile.comments');
Route::get(__('perfil') . '/{username}/historial', [App\Http\Controllers\UserController::class, 'history'])->middleware(['auth'])->name('profile.history');

Route::get(__('perfil') . '/{username}', [App\Http\Controllers\UserController::class, 'index'])->name('profile');
Route::get(__('configuracion'), [App\Http\Controllers\UserController::class, 'settings'])->middleware(['auth'])->name('settings');
Route::post(__('configuracion'), [App\Http\Controllers\UserController::class, 'update'])->middleware(['auth', 'demo'])->name('settings.update');
Route::get(__('clasificacion'), [App\Http\Controllers\UserController::class, 'leaderboard'])->name('leaderboard');

// Suscripción
Route::controller(\App\Http\Controllers\SubscriptionController::class)->middleware(['auth'])->name('subscription.')->group(function () {
    Route::get('suscripcion', 'index')->name('index');
    Route::get('facturacion', 'billing')->name('billing');
    Route::get('factura/{id}', 'invoice')->name('invoice');
    Route::get('pago', 'payment')->name('payment');
    Route::get('pago-pendiente', 'pending')->name('pending');
    Route::get('pago-cancelado', 'cancelled')->name('cancelled');
    Route::get('pago-completado', 'completed')->name('completed');
    Route::post('pago', 'store');
    Route::post('suscripcion', 'update')->name('update')->middleware('demo');
    Route::post('facturacion', 'cancelSubscription')->name('cancelSubscription')->middleware('demo');
});

// Comunidad
Route::get(__('discusiones'), [App\Http\Controllers\BrowseController::class, 'discussions'])->name('discussions');
Route::get(__('discusion') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'discussion'])->name('discussion');
Route::post(__('crear-discusion'), [App\Http\Controllers\BrowseController::class, 'discussionStore'])->name('discussions.create');
// Personas
Route::get(__('actores'), [App\Http\Controllers\BrowseController::class, 'peoples'])->name('peoples');
Route::get(__('actor') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'people'])->name('people');

// Colección
Route::get(__('colecciones'), [App\Http\Controllers\BrowseController::class, 'collections'])->name('collections');
Route::get(__('coleccion') . '/{slug}', [App\Http\Controllers\BrowseController::class, 'collection'])->name('collection');

// Blog
Route::get(__('blog'), [App\Http\Controllers\ArticleController::class, 'index'])->name('blog');
Route::get(__('articulo') . '/{slug}', [App\Http\Controllers\ArticleController::class, 'show'])->name('article');

// Página
Route::get(__('pagina') . '/{slug}', [App\Http\Controllers\PageController::class, 'show'])->name('page');
Route::get(__('contacto'), [App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post(__('contacto'), [App\Http\Controllers\PageController::class, 'contactmail'])->name('contact.submit');

// Ajax
Route::prefix('ajax')->name('ajax.')->middleware(['auth'])->group(function () {
    Route::post('reaction', [App\Http\Controllers\AjaxController::class, 'reaction'])->name('reaction');
});

Route::get('lang/{lang}', ['as' => 'lang.switch', 'uses' => 'App\Http\Controllers\AjaxController@switchLang']);

// Sitemap
Route::get('sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemap_main.xml', [App\Http\Controllers\SitemapController::class, 'main'])->name('sitemap.main');
Route::get('sitemap_post_{page}.xml', [App\Http\Controllers\SitemapController::class, 'post'])->name('sitemap.post');
Route::get('sitemap_episode_{page}.xml', [App\Http\Controllers\SitemapController::class, 'episode'])->name('sitemap.episode');
Route::get('sitemap_people_{page}.xml', [App\Http\Controllers\SitemapController::class, 'people'])->name('sitemap.people');
Route::get('sitemap_genre_{page}.xml', [App\Http\Controllers\SitemapController::class, 'genre'])->name('sitemap.genre');

// Rutas de Webhook

Route::post('webhooks/paypal', [\App\Http\Controllers\WebhookController::class, 'paypal'])->name('webhooks.paypal');
Route::post('webhooks/stripe', [\App\Http\Controllers\WebhookController::class, 'stripe'])->name('webhooks.stripe');

// Instalar
Route::controller(App\Http\Controllers\InstallController::class)->name('install.')->group(function () {
    Route::get('install/index', 'index')->name('index');
    Route::get('install/config', 'config')->name('config');
    Route::get('install/complete', 'complete')->name('complete');
    Route::post('install/config', 'store')->name('store');
});
require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
