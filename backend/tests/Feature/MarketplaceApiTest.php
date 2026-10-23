<?php

namespace Tests\Feature;

use App\Models\Bakery;
use App\Models\BakeryProduct;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Inn;
use App\Models\InnRoom;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\RestaurantProduct;
use App\Models\Tour;
use App\Models\TourImage;
use App\Models\TourInclude;
use App\Models\TourRecommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): Provider
    {
        return Provider::create([
            'business_name' => 'Seven Excursions',
            'owner_name' => 'Carlos Archbold',
            'email' => 'excursions@seven.test',
            'phone' => '+57 300 000 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    public function test_lists_only_approved_tours_with_relations(): void
    {
        $provider = $this->provider();

        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Johnny Cay',
            'slug' => 'johnny-cay',
            'subtitle' => 'Un paraíso a pocos minutos',
            'description' => 'Isla de ensueño con aguas cristalinas.',
            'category' => 'Tours y experiencias',
            'location' => 'San Andrés, Colombia',
            'duration' => '4 horas',
            'price' => 170000,
            'rating' => 4.8,
            'reviews' => 1300,
            'cover_image' => 'tours/johnny-cay/cover.svg',
            'status' => 'approved',
        ]);

        TourImage::create(['tour_id' => $tour->id, 'image' => 'tours/johnny-cay/photo-1.svg', 'order' => 0]);
        TourImage::create(['tour_id' => $tour->id, 'image' => 'tours/johnny-cay/photo-2.svg', 'order' => 1]);
        TourInclude::create(['tour_id' => $tour->id, 'icon' => 'boat-outline', 'name' => 'Transporte']);
        TourRecommendation::create(['tour_id' => $tour->id, 'icon' => 'sunny-outline', 'name' => 'Bloqueador']);

        Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Oculto',
            'slug' => 'tour-oculto',
            'price' => 1000,
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/tours')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'johnny-cay')
            ->assertJsonPath('data.0.price', 170000)
            ->assertJsonCount(2, 'data.0.images')
            ->assertJsonCount(1, 'data.0.includes')
            ->assertJsonCount(1, 'data.0.recommendations');
    }

    public function test_shows_tour_by_slug(): void
    {
        $provider = $this->provider();
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Acuario de Mantarrayas',
            'slug' => 'acuario-mantarrayas',
            'price' => 120000,
            'status' => 'approved',
        ]);
        TourImage::create(['tour_id' => $tour->id, 'image' => 'tours/acuario/photo.svg', 'order' => 0]);

        $this->getJson('/api/v1/tours/acuario-mantarrayas')
            ->assertOk()
            ->assertJsonPath('data.slug', 'acuario-mantarrayas')
            ->assertJsonPath('data.id', $tour->id);
    }

    public function test_returns_404_for_missing_or_hidden_tour(): void
    {
        $provider = $this->provider();
        Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Oculto',
            'slug' => 'invisible',
            'price' => 1000,
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/tours/invisible')->assertNotFound();
        $this->getJson('/api/v1/tours/no-existe')->assertNotFound();
    }

    public function test_lists_restaurants_with_products(): void
    {
        $provider = Provider::create([
            'business_name' => 'La Regatta Cocina Caribe',
            'type' => 'restaurant',
            'status' => 'approved',
        ]);

        $restaurant = Restaurant::create([
            'provider_id' => $provider->id,
            'name' => 'La Regatta',
            'slug' => 'la-regatta',
            'category' => 'Mariscos',
            'rating' => 4.7,
            'price_range' => '$$',
            'status' => 'approved',
        ]);

        RestaurantProduct::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Langosta al ajillo',
            'description' => 'Langosta fresca al ajo con arroz de coco.',
            'price' => 95000,
            'category' => 'Platos fuertes',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/restaurants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'La Regatta')
            ->assertJsonCount(1, 'data.0.products')
            ->assertJsonPath('data.0.products.0.name', 'Langosta al ajillo')
            ->assertJsonPath('data.0.products.0.price', 95000);
    }

    public function test_shows_restaurant_by_slug(): void
    {
        $provider = $this->provider();
        Restaurant::create([
            'provider_id' => $provider->id,
            'name' => 'La Regatta',
            'slug' => 'la-regatta',
            'status' => 'approved',
        ]);

        $this->getJson('/api/v1/restaurants/la-regatta')
            ->assertOk()
            ->assertJsonPath('data.slug', 'la-regatta');
    }

    public function test_lists_bakeries_with_products(): void
    {
        $provider = Provider::create([
            'business_name' => 'Panadería y Repostería Suzette',
            'type' => 'bakery',
            'status' => 'approved',
        ]);

        $bakery = Bakery::create([
            'provider_id' => $provider->id,
            'name' => 'Panadería Suzette',
            'slug' => 'panaderia-suzette',
            'category' => 'Panadería artesanal',
            'rating' => 4.6,
            'price_range' => '$$',
            'status' => 'approved',
        ]);

        BakeryProduct::create([
            'bakery_id' => $bakery->id,
            'name' => 'Pan de coco',
            'description' => 'Pan tradicional horneado al carbón.',
            'price' => 5000,
            'category' => 'Panes',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/bakeries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Panadería Suzette')
            ->assertJsonCount(1, 'data.0.products')
            ->assertJsonPath('data.0.products.0.name', 'Pan de coco')
            ->assertJsonPath('data.0.products.0.price', 5000);
    }

    public function test_shows_bakery_by_slug_and_hides_not_approved(): void
    {
        $provider = $this->provider();
        Bakery::create([
            'provider_id' => $provider->id,
            'name' => 'Panadería Trigo Dorado',
            'slug' => 'panaderia-trigo-dorado',
            'status' => 'approved',
        ]);
        Bakery::create([
            'provider_id' => $provider->id,
            'name' => 'Panadería Oculta',
            'slug' => 'panaderia-oculta',
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/bakeries/panaderia-trigo-dorado')
            ->assertOk()
            ->assertJsonPath('data.slug', 'panaderia-trigo-dorado');
        $this->getJson('/api/v1/bakeries/panaderia-oculta')->assertNotFound();
    }

    public function test_lists_inns_with_rooms(): void
    {
        $provider = $this->provider();
        $inn = Inn::create([
            'provider_id' => $provider->id,
            'name' => 'Posada El Coco Loco',
            'slug' => 'posada-el-coco-loco',
            'price' => 280000,
            'services' => ['Desayuno', 'Wi-Fi'],
            'availability' => true,
            'rating' => 4.6,
            'status' => 'approved',
        ]);
        InnRoom::create([
            'inn_id' => $inn->id,
            'name' => 'Habitación estándar',
            'price' => 280000,
            'capacity' => 2,
        ]);

        $this->getJson('/api/v1/inns')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'posada-el-coco-loco')
            ->assertJsonCount(1, 'data.0.rooms')
            ->assertJsonPath('data.0.price', 280000);
    }

    public function test_shows_inn_by_slug(): void
    {
        $provider = $this->provider();
        Inn::create([
            'provider_id' => $provider->id,
            'name' => 'Casa Raizal',
            'slug' => 'casa-raizal-san-andres',
            'price' => 220000,
            'status' => 'approved',
        ]);

        $this->getJson('/api/v1/inns/casa-raizal-san-andres')
            ->assertOk()
            ->assertJsonPath('data.slug', 'casa-raizal-san-andres');
    }

    public function test_lists_active_banners_only(): void
    {
        Banner::create([
            'title' => 'San Andrés te espera',
            'status' => 'active',
            'order' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        Banner::create([
            'title' => 'Fuera de rango',
            'status' => 'active',
            'order' => 2,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5),
        ]);
        Banner::create([
            'title' => 'Inactivo',
            'status' => 'inactive',
            'order' => 3,
        ]);

        $this->getJson('/api/v1/banners')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'San Andrés te espera');
    }

    public function test_lists_active_promotions(): void
    {
        $provider = $this->provider();
        Promotion::create([
            'provider_id' => $provider->id,
            'title' => '20% descuento cena romántica',
            'discount' => 20,
            'status' => 'active',
        ]);
        Promotion::create([
            'provider_id' => $provider->id,
            'title' => 'Oculta',
            'discount' => 10,
            'status' => 'inactive',
        ]);

        $this->getJson('/api/v1/promotions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '20% descuento cena romántica')
            ->assertJsonPath('data.0.discount', 20);
    }

    public function test_lists_only_published_news_with_its_provider(): void
    {
        $provider = $this->provider();

        News::create([
            'title' => 'Vuelve el Festival de la Arepa de Huevo',
            'slug' => 'festival-arepa-huevo',
            'kind' => News::KIND_EVENT,
            'excerpt' => 'Tres días de música en vivo.',
            'provider_id' => $provider->id,
            'published_at' => now()->subDay(),
            'status' => News::STATUS_PUBLISHED,
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'festival-arepa-huevo')
            ->assertJsonPath('data.0.kind', News::KIND_EVENT)
            ->assertJsonPath('data.0.provider.business_name', 'Seven Excursions');
    }

    /**
     * Un borrador y una nota programada son los dos motivos por los que el feed
     * público puede verse corto, y por eso se prueban juntos: un `status` bien
     * filtrado pero una fecha mal tratada sacaría la nota programada al feed.
     */
    public function test_hides_drafts_and_scheduled_news_from_the_public_feed(): void
    {
        News::create([
            'title' => 'Borrador interno',
            'slug' => 'borrador-interno',
            'status' => News::STATUS_DRAFT,
        ]);

        News::create([
            'title' => 'Archivada',
            'slug' => 'archivada',
            'status' => News::STATUS_ARCHIVED,
        ]);

        News::create([
            'title' => 'Programada para la semana que viene',
            'slug' => 'programada',
            'status' => News::STATUS_PUBLISHED,
            'published_at' => now()->addWeek(),
        ]);

        News::create([
            'title' => 'Vigente',
            'slug' => 'vigente',
            'status' => News::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'vigente');
    }

    /**
     * El orden importa en un feed: las destacadas primero y, dentro de cada
     * grupo, lo más reciente. Sin el desempate por `id` dos notas con la misma
     * fecha saldrían en orden distinto en cada recarga.
     */
    public function test_orders_news_by_featured_then_most_recent(): void
    {
        News::create([
            'title' => 'Destacada antigua',
            'slug' => 'destacada-antigua',
            'is_featured' => true,
            'order' => 1,
            'published_at' => now()->subMonth(),
            'status' => News::STATUS_PUBLISHED,
        ]);

        News::create([
            'title' => 'Destacada nueva',
            'slug' => 'destacada-nueva',
            'is_featured' => true,
            'order' => 2,
            'published_at' => now()->subDay(),
            'status' => News::STATUS_PUBLISHED,
        ]);

        News::create([
            'title' => 'Reciente sin destacar',
            'slug' => 'reciente-sin-destacar',
            'published_at' => now()->subMinutes(10),
            'status' => News::STATUS_PUBLISHED,
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'destacada-antigua')
            ->assertJsonPath('data.1.slug', 'destacada-nueva')
            ->assertJsonPath('data.2.slug', 'reciente-sin-destacar');
    }

    public function test_shows_news_by_slug(): void
    {
        $news = News::create([
            'title' => 'Vuelve el Festival de la Arepa de Huevo',
            'slug' => 'festival-arepa-huevo',
            'body' => 'Cuerpo completo de la nota.',
            'status' => News::STATUS_PUBLISHED,
        ]);

        $this->getJson('/api/v1/news/festival-arepa-huevo')
            ->assertOk()
            ->assertJsonPath('data.id', $news->id)
            ->assertJsonPath('data.body', 'Cuerpo completo de la nota.');
    }

    public function test_returns_404_for_missing_draft_or_scheduled_news(): void
    {
        News::create([
            'title' => 'Borrador interno',
            'slug' => 'borrador-interno',
            'status' => News::STATUS_DRAFT,
        ]);

        News::create([
            'title' => 'Programada',
            'slug' => 'programada',
            'status' => News::STATUS_PUBLISHED,
            'published_at' => now()->addWeek(),
        ]);

        $this->getJson('/api/v1/news/borrador-interno')->assertNotFound();
        $this->getJson('/api/v1/news/programada')->assertNotFound();
        $this->getJson('/api/v1/news/no-existe')->assertNotFound();
    }

    /**
     * El slug es la única forma de compartir una nota, así que se autogenera a
     * partir del título cuando el backoffice no lo envía.
     */
    public function test_generates_the_slug_from_the_title_when_missing(): void
    {
        $news = News::create([
            'title' => 'Cómo moverse por la isla sin gastar de más',
            'slug' => '',
        ]);

        $this->assertSame('como-moverse-por-la-isla-sin-gastar-de-mas', $news->slug);
    }

    public function test_categories_expose_order_and_status(): void
    {
        $category = Category::create([
            'slug' => 'tours',
            'name' => 'Tours',
            'icon' => 'boat-outline',
            'sort_order' => 7,
            'is_active' => true,
            'order' => 7,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.order', 7)
            ->assertJsonPath('data.0.status', 'active');
    }
}
