<?php

use App\Http\Controllers\Api\V1\AccommodationController;
use App\Http\Controllers\Api\V1\Admin\AccommodationAdminController;
use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\BakeryAdminController;
use App\Http\Controllers\Api\V1\Admin\BannerAdminController;
use App\Http\Controllers\Api\V1\Admin\BookingAdminController;
use App\Http\Controllers\Api\V1\Admin\BusinessAdminController;
use App\Http\Controllers\Api\V1\Admin\CategoryAdminController;
use App\Http\Controllers\Api\V1\Admin\CommentAdminController;
use App\Http\Controllers\Api\V1\Admin\ExperienceAdminController;
use App\Http\Controllers\Api\V1\Admin\InnAdminController;
use App\Http\Controllers\Api\V1\Admin\MediaAdminController;
use App\Http\Controllers\Api\V1\Admin\NewsAdminController;
use App\Http\Controllers\Api\V1\Admin\PromotionAdminController;
use App\Http\Controllers\Api\V1\Admin\ProviderAdminController;
use App\Http\Controllers\Api\V1\Admin\RestaurantAdminController;
use App\Http\Controllers\Api\V1\Admin\TourAdminController;
use App\Http\Controllers\Api\V1\ApiIntroController;
use App\Http\Controllers\Api\V1\AssistantController;
use App\Http\Controllers\Api\V1\BakeryController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ExperienceController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InnController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\Provider\ProviderAuthController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\TourController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Entry points grouped by domain. Controllers live in
| App\Http\Controllers\Api\V1.
|
*/

Route::get('/', ApiIntroController::class);

Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show'])
    ->middleware('http.cache');

Route::prefix('tours')->group(function () {
    Route::get('/', [TourController::class, 'index']);
    Route::get('{tour}', [TourController::class, 'show'])->where('tour', '[a-z0-9\-]+');
    Route::get('{tour}/comments', [CommentController::class, 'tourIndex'])->where('tour', '[a-z0-9\-]+');
    Route::post('{tour}/comments', [CommentController::class, 'tourStore'])->middleware('throttle:comments')->where('tour', '[a-z0-9\-]+');
});

Route::prefix('restaurants')->group(function () {
    Route::get('/', [RestaurantController::class, 'index']);
    Route::get('{restaurant}', [RestaurantController::class, 'show'])->where('restaurant', '[a-z0-9\-]+');
    Route::get('{restaurant}/comments', [CommentController::class, 'restaurantIndex'])->where('restaurant', '[a-z0-9\-]+');
    Route::post('{restaurant}/comments', [CommentController::class, 'restaurantStore'])->middleware('throttle:comments')->where('restaurant', '[a-z0-9\-]+');
});

Route::prefix('inns')->group(function () {
    Route::get('/', [InnController::class, 'index']);
    Route::get('{inn}', [InnController::class, 'show'])->where('inn', '[a-z0-9\-]+');
    Route::get('{inn}/comments', [CommentController::class, 'innIndex'])->where('inn', '[a-z0-9\-]+');
    Route::post('{inn}/comments', [CommentController::class, 'innStore'])->middleware('throttle:comments')->where('inn', '[a-z0-9\-]+');
});

Route::prefix('bakeries')->group(function () {
    Route::get('/', [BakeryController::class, 'index']);
    Route::get('{bakery}', [BakeryController::class, 'show'])->where('bakery', '[a-z0-9\-]+');
});

Route::get('banners', [BannerController::class, 'index'])->middleware('http.cache');
Route::get('promotions', [PromotionController::class, 'index'])->middleware('http.cache');

// Noticias: por slug y no por id para que una nota se pueda compartir por
// enlace. La restricción de caracteres replica la de tours/inns/restaurants.
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->middleware('http.cache');
    Route::get('{slug}', [NewsController::class, 'show'])->middleware('http.cache')
        ->where('slug', '[a-z0-9\-]+');
});

Route::prefix('businesses')->group(function () {
    Route::get('/', [BusinessController::class, 'index']);
    Route::get('{business}', [BusinessController::class, 'show'])->whereNumber('business');
    Route::get('{business}/availability', [BusinessController::class, 'availability'])->whereNumber('business');
});

Route::prefix('accommodations')->group(function () {
    Route::get('/', [AccommodationController::class, 'index']);
    Route::get('{accommodation}', [AccommodationController::class, 'show'])->whereNumber('accommodation');
    Route::get('{accommodation}/availability', [AccommodationController::class, 'availability'])->whereNumber('accommodation');
});

Route::prefix('experiences')->group(function () {
    Route::get('/', [ExperienceController::class, 'index']);
    Route::get('{experience}', [ExperienceController::class, 'show'])->whereNumber('experience');
    Route::get('{experience}/availability', [ExperienceController::class, 'availability'])->whereNumber('experience');
});

Route::prefix('bookings')->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::get('{bookingCode}', [BookingController::class, 'show']);
    Route::put('{bookingCode}', [BookingController::class, 'update']);
    Route::delete('{bookingCode}', [BookingController::class, 'destroy']);
});

Route::get('health', HealthController::class);

/*
|--------------------------------------------------------------------------
| Asistente turístico de SEVEN
|--------------------------------------------------------------------------
| Endpoint público y anónimo. Cada mensaje puede disparar varias llamadas
| facturables al modelo, así que lleva su propio rate limit (throttle:assistant)
| por dispositivo e IP. El asistente solo lee catálogo aprobado.
*/
Route::post('assistant/messages', [AssistantController::class, 'message'])
    ->middleware('throttle:assistant');

/*
|--------------------------------------------------------------------------
| Autenticación administrativa (Sanctum)
|--------------------------------------------------------------------------
| El login es el único endpoint público del panel y lleva throttle
| propio (6 por minuto por email + IP). Todo lo demás corre bajo
| auth:sanctum: exige un usuario real, no el token compartido legacy.
*/
Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('login');

    // Contexto administrativo: no basta con "cualquier token Sanctum".
    // Se exige autenticación, ability `admin` y un rol de backoffice, de
    // modo que un token `provider` (u otra superficie futura) no pueda
    // listar ni crear tokens personales desde aquí.
    Route::middleware(['auth:sanctum', 'ability:admin', 'role:SUPER_ADMIN,ADMIN_SEVEN'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('me', [AdminAuthController::class, 'me'])->name('me');

        Route::get('tokens', [AdminAuthController::class, 'tokens'])->name('tokens.index');
        Route::post('tokens', [AdminAuthController::class, 'storeToken'])->name('tokens.store');
        Route::delete('tokens/others', [AdminAuthController::class, 'revokeOtherTokens'])->name('tokens.others');
        Route::delete('tokens/{tokenId}', [AdminAuthController::class, 'revokeToken'])
            ->whereNumber('tokenId')
            ->name('tokens.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Backoffice (panel de administración)
|--------------------------------------------------------------------------
| Autenticación: admin.auth (token de Sanctum o, en transición, el token
| compartido legacy — ver app/Http/Middleware/AdminAuthMiddleware) y
| autorización: role (SUPER_ADMIN o ADMIN_SEVEN). throttle:admin limita a
| 60/min por def. CRUD completo del catálogo.
*/
Route::prefix('admin')->middleware(['admin.auth', 'ability:admin', 'role:SUPER_ADMIN,ADMIN_SEVEN', 'throttle:admin'])->group(function () {
    Route::apiResource('providers', ProviderAdminController::class)
        ->middleware('permission:providers.manage')->names('admin.providers');

    Route::apiResource('categories', CategoryAdminController::class)
        ->middleware('permission:catalog.manage')->names('admin.categories');
    Route::apiResource('banners', BannerAdminController::class)
        ->middleware('permission:catalog.manage')->names('admin.banners');
    Route::apiResource('promotions', PromotionAdminController::class)
        ->middleware('permission:catalog.manage')->names('admin.promotions');
    Route::apiResource('media', MediaAdminController::class)->only(['index', 'show', 'store', 'update', 'destroy'])
        ->middleware('permission:catalog.manage')->names('admin.media');

    Route::apiResource('news', NewsAdminController::class)
        ->middleware('permission:content.manage')->names('admin.news');
    Route::apiResource('businesses', BusinessAdminController::class)
        ->middleware('permission:content.manage')->names('admin.businesses');
    Route::apiResource('tours', TourAdminController::class)
        ->middleware('permission:content.manage')->names('admin.tours');
    Route::apiResource('inns', InnAdminController::class)
        ->middleware('permission:content.manage')->names('admin.inns');
    Route::apiResource('restaurants', RestaurantAdminController::class)
        ->middleware('permission:content.manage')->names('admin.restaurants');
    Route::apiResource('bakeries', BakeryAdminController::class)
        ->middleware('permission:content.manage')->names('admin.bakeries');
    Route::apiResource('experiences', ExperienceAdminController::class)
        ->middleware('permission:content.manage')->names('admin.experiences');
    Route::apiResource('accommodations', AccommodationAdminController::class)
        ->middleware('permission:content.manage')->names('admin.accommodations');

    Route::prefix('bookings')->name('admin.bookings.')->middleware('permission:bookings.manage')->group(function () {
        Route::get('/', [BookingAdminController::class, 'index']);
        Route::get('{bookingCode}', [BookingAdminController::class, 'show']);
        Route::patch('{bookingCode}/status', [BookingAdminController::class, 'updateStatus']);
    });

    Route::prefix('comments')->name('admin.comments.')->middleware('permission:comments.moderate')->group(function () {
        Route::get('/', [CommentAdminController::class, 'index']);
        Route::patch('{comment}/status', [CommentAdminController::class, 'updateStatus']);
        Route::delete('{comment}', [CommentAdminController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Autenticación del proveedor (Sanctum con abilities)
|--------------------------------------------------------------------------
| Flujo independiente del backoffice: el login es público (throttle
| propio) y solo acepta cuentas PROVEEDOR_TURISTICO con ficha vinculada;
| emite un token con abilidad `provider` que el resto del panel exige.
*/
Route::prefix('provider/auth')->name('provider.auth.')->group(function () {
    Route::post('login', [ProviderAuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('login');

    Route::middleware(['auth:sanctum', 'role:PROVEEDOR_TURISTICO', 'provider.auth'])->group(function () {
        Route::post('logout', [ProviderAuthController::class, 'logout'])->name('logout');
        Route::get('me', [ProviderAuthController::class, 'me'])->name('me');

        // Tokens personales del proveedor: siempre con abilidad `provider`,
        // nunca `admin`. Un token admin no llega aquí (provider.auth).
        Route::get('tokens', [ProviderAuthController::class, 'tokens'])->name('tokens.index');
        Route::post('tokens', [ProviderAuthController::class, 'storeToken'])->name('tokens.store');
        Route::delete('tokens/others', [ProviderAuthController::class, 'revokeOtherTokens'])->name('tokens.others');
        Route::delete('tokens/{tokenId}', [ProviderAuthController::class, 'revokeToken'])
            ->whereNumber('tokenId')
            ->name('tokens.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Panel del proveedor (multi-tenant)
|--------------------------------------------------------------------------
| Mismos controllers que el backoffice, pero con el rol del dueño, el
| middleware provider.auth (Sanctum con abilidad `provider`, legacy y
| tokens admin excluidos) y provider.scope: cada request sale filtrado
| por SU provider_id, el alta/edición fuerza el provider_id del tenant y
| un recurso ajeno responde 404 (AdminCrudController + policies).
| Recursos platform-only (categories, banners, comments, media) quedan
| solo en /admin/*.
*/
Route::prefix('provider')->middleware(['admin.auth', 'provider.auth', 'role:PROVEEDOR_TURISTICO', 'provider.scope', 'throttle:admin'])->group(function () {
    Route::apiResource('providers', ProviderAdminController::class)->only(['index', 'show', 'update'])->names('provider.providers');
    Route::apiResource('businesses', BusinessAdminController::class)->names('provider.businesses');
    Route::apiResource('accommodations', AccommodationAdminController::class)->names('provider.accommodations');
    Route::apiResource('experiences', ExperienceAdminController::class)->names('provider.experiences');
    Route::apiResource('tours', TourAdminController::class)->names('provider.tours');
    Route::apiResource('restaurants', RestaurantAdminController::class)->names('provider.restaurants');
    Route::apiResource('inns', InnAdminController::class)->names('provider.inns');
    Route::apiResource('bakeries', BakeryAdminController::class)->names('provider.bakeries');
    Route::apiResource('promotions', PromotionAdminController::class)->names('provider.promotions');
    Route::apiResource('news', NewsAdminController::class)->names('provider.news');
    Route::apiResource('media', MediaAdminController::class)->only(['index', 'show', 'store', 'update', 'destroy'])->names('provider.media');

    Route::prefix('bookings')->name('provider.bookings.')->group(function () {
        Route::get('/', [BookingAdminController::class, 'index']);
        Route::get('{bookingCode}', [BookingAdminController::class, 'show']);
        Route::patch('{bookingCode}/status', [BookingAdminController::class, 'updateStatus']);
    });
});
