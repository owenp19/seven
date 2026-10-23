<?php

namespace Database\Seeders;

use App\Models\Bakery;
use App\Models\BakeryProduct;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Inn;
use App\Models\InnRoom;
use App\Models\Media;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\RestaurantProduct;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourImage;
use App\Models\TourInclude;
use App\Models\TourRecommendation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * MarketplaceSeeder
 *
 * Seeds the marketplace verticals with REAL San Andrés businesses:
 * tours (Johnny Cay, Acuario & Haynes Cay, Vuelta a la Isla),
 * restaurants (La Regatta, Donde Francesca, Miss Celia), native
 * inns (Miss Trinie, Atardecer Isleño, Miss Casira) and bakeries
 * (Suzette, Trigo Dorado, Susany), each with its provider, menu and
 * products, plus home banners and promotions.
 *
 * Cover photos and tour galleries are stored under
 * `storage/app/public/{banners,tours,restaurants,inns,bakeries}/`
 * with SEO file names. Download them first with the companion script
 * `scripts/import-media.ps1`; any missing photo falls back to a
 * branded SVG so every URL always resolves.
 *
 * Data verified on San Andrés (Sep 2026). Main sources:
 * - Johnny Cay / Acuario / Vuelta a la Isla: exploriia.com, civitatis
 *   (San Andrés tours), pos.do (precios), islaventura.com.co, rivastours.co.
 * - La Regatta: restaurantelaregatta.com (Av Newball, junto al Club
 *   Náutico; Cra. 1 #3-121; reservaslaregatta@ibtsas.co).
 * - Donde Francesca: playas de San Luis (Sound Bay); valoración ~4.1.
 * - Miss Celia: Av Newball con Av Colón, frente al Club Náutico;
 *   tel. +57 8 5131062 / +57 5126495; rondón, guiso de caracol.
 * - Posada Nativa Miss Trinie: Av. 20 de Julio con Crr San Luis #4-16,
 *   desde ~COP 83.850/noche (fuente pos.do); Atardecer Isleño y Miss
 *   Casira: islandhotels.net / ohhotel.co / sanandresislas.com.co.
 * - Panaderías: Suzette (Av. Colombia, Sarie Bay, tel. +57 8 5127867),
 *   Trigo Dorado (Av. Principal School House, tel. +57 8 5121227),
 *   Susany (Av. 20 de Julio, tel. +57 8 512239) via sanandresislas.com.co.
 * - Fotografías: Wikimedia Commons (CC BY-SA), landings vía
 *   `scripts/import-media.ps1`.
 *
 * Run with: php artisan migrate:fresh --seed
 */
class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureCategories();
        $this->seedUsers();

        $operators = [
            'johnny-cay' => $this->provider('Exploria Tours San Andrés', 'tour_operator', 'Exploria Tours'),
            'acuario-haynes-cay' => $this->provider('Islaventura Tours', 'tour_operator', 'Islaventura'),
            'vuelta-isla-chiva' => $this->provider('Rivas Tours San Andrés', 'tour_operator', 'Rivas Tours'),
        ];

        $restaurantProviders = [
            'la-regatta' => $this->provider('La Regatta', 'restaurant', 'La Regatta'),
            'donde-francesca' => $this->provider('Donde Francesca', 'restaurant', 'Donde Francesca'),
            'miss-celia' => $this->provider('Restaurante Miss Celia', 'restaurant', 'Miss Celia'),
        ];

        $innProviders = [
            'posada-nativa-miss-trinie' => $this->provider('Posada Nativa Miss Trinie', 'native_inn', 'Miss Trinie'),
            'posada-nativa-atardecer-isleno' => $this->provider('Posada Nativa Atardecer Isleño', 'native_inn', 'Atardecer Isleño'),
            'posada-nativa-miss-casira' => $this->provider('Posada Nativa Miss Casira', 'native_inn', 'Miss Casira'),
        ];

        $bakeryProviders = [
            'panaderia-suzette' => $this->provider('Panadería y Repostería Suzette', 'bakery', 'Suzette'),
            'panaderia-trigo-dorado' => $this->provider('Panadería y Repostería Trigo Dorado', 'bakery', 'Trigo Dorado'),
            'panaderia-susany' => $this->provider('Panadería y Bizcochería Susany', 'bakery', 'Susany'),
        ];

        $this->seedTours($operators);
        $this->seedRestaurants($restaurantProviders);
        $this->seedInns($innProviders);
        $this->seedBakeries($bakeryProviders);
        $this->seedBanners();
        $this->seedPromotions($operators['johnny-cay'], $restaurantProviders['la-regatta']);
        $this->seedNews($operators['johnny-cay'], $innProviders['posada-nativa-miss-trinie']);
        $this->linkOperatorToProvider($operators['johnny-cay']);
    }

    /**
     * El panel /provider/* necesita un tenant real: la cuenta del operador
     * demo queda como dueña de su proveedor (relación providers.user_id).
     * Idempotente: solo enlaza si la cuenta todavía no tiene proveedor.
     */
    private function linkOperatorToProvider(Provider $provider): void
    {
        $operador = User::where('email', 'operador@seven.test')->first();

        if ($operador !== null && ! $operador->provider()->exists()) {
            $provider->update(['user_id' => $operador->id]);
        }
    }

    private function ensureCategories(): void
    {
        // Solo asegura la vertical que necesita este seeder. No recorre el
        // reescribiendo `status`: CategorySeeder acaba de retirar las
        // verticales legacy y un bucle `update()` sobre todas las volvería
        // a activar. El modelo Category ya realinea `order`/`status` con
        // `sort_order`/`is_active` en cada guardado.
        Category::firstOrCreate(
            ['slug' => 'tours'],
            [
                'name' => 'Tours',
                'icon' => 'boat-outline',
                'description' => 'Tours y actividades para descubrir el mar Caribe',
                'order' => 1,
                'status' => Category::ACTIVE,
            ]
        );
    }

    private function seedUsers(): void
    {
        // El catálogo de roles debe existir antes de asignar; RoleSeeder es
        // idempotente, así que llamarlo aquí tolera un `db:seed` parcial.
        $this->call(RoleSeeder::class);

        // La columna string `role` se conserva por compatibilidad, pero la
        // autorización del panel lee la pivote `role_user`.
        User::updateOrCreate(
            ['email' => 'admin@seven.test'],
            ['name' => 'Administrador SEVEN', 'password' => 'password', 'role' => 'admin']
        )->assignRole(Role::SUPER_ADMIN);

        User::updateOrCreate(
            ['email' => 'operador@seven.test'],
            ['name' => 'Operador Demo', 'password' => 'password', 'role' => 'provider']
        )->assignRole(Role::PROVEEDOR_TURISTICO);

        User::updateOrCreate(
            ['email' => 'viajero@seven.test'],
            ['name' => 'Viajero Demo', 'password' => 'password', 'role' => 'customer']
        );
    }

    private function provider(string $businessName, string $type, string $ownerName, ?string $address = null): Provider
    {
        $slug = str(\Str::slug($businessName))->slug();

        return Provider::updateOrCreate(
            ['business_name' => $businessName],
            [
                'owner_name' => $ownerName,
                'email' => $slug.'@seven.test',
                'phone' => '+57 8 512 0000',
                'address' => $address ?? 'San Andrés Isla, Colombia',
                'logo' => $this->image('providers', $slug, $businessName, 200, 200),
                'type' => $type,
                'status' => 'approved',
                'verification_status' => 'verified',
            ]
        );
    }

    /**
     * Tours verified from the island's main operators (Exploria,
     * Islaventura, Rivas Tours).
     */
    private function seedTours(array $providers): void
    {
        $tours = [
            [
                'provider' => 'johnny-cay',
                'name' => 'Johnny Cay',
                'slug' => 'johnny-cay',
                'subtitle' => 'El cayo de la arena blanca y el mar de siete colores',
                'category' => 'Tours',
                'location' => 'Parque Regional Johnny Cay, San Andrés',
                'duration' => '5 horas',
                'price' => 120000,
                'rating' => 4.8,
                'reviews' => 1300,
                'description' => 'Navega en lancha rápida hasta el Parque Regional Natural Johnny Cay, el cayo más grande de San Andrés. Arena blanca, palmeras y aguas turquesa para nadar y descansar durante toda la mañana, con guía local y transporte ida y vuelta desde el muelle.',
                'includes' => [
                    ['icon' => 'boat-outline', 'name' => 'Transporte en lancha ida y vuelta'],
                    ['icon' => 'shield-checkmark-outline', 'name' => 'Guía local certificado'],
                    ['icon' => 'water-outline', 'name' => 'Chaleco salvavidas'],
                    ['icon' => 'leaf-outline', 'name' => 'Impuesto ecológico CORALINA'],
                    ['icon' => 'cafe-outline', 'name' => 'Tiempo libre en playa'],
                ],
                'recommendations' => [
                    ['icon' => 'sunny-outline', 'name' => 'Bloqueador solar y sombrero'],
                    ['icon' => 'glasses-outline', 'name' => 'Gafas de sol'],
                    ['icon' => 'water-outline', 'name' => 'Toalla y aquashoes'],
                    ['icon' => 'cash-outline', 'name' => 'Efectivo para almuerzo y zarpe'],
                ],
            ],
            [
                'provider' => 'acuario-haynes-cay',
                'name' => 'Acuario Natural y Haynes Cay',
                'slug' => 'acuario-haynes-cay',
                'subtitle' => 'Snorkel entre mantarrayas y peces de colores',
                'category' => 'Tours',
                'location' => 'Muelle Principal, San Andrés',
                'duration' => '6 horas',
                'price' => 100000,
                'rating' => 4.7,
                'reviews' => 900,
                'description' => 'Salida al acuario natural de Rose Cay, donde las mantarrayas se acercan al agua a la cintura para verte de cerca. Continúa hacia Haynes Cay entre manglares, con guía certificado que te acompaña durante todo el recorrido de snorkel.',
                'includes' => [
                    ['icon' => 'boat-outline', 'name' => 'Transporte en lancha'],
                    ['icon' => 'water-outline', 'name' => 'Snorkel guiado en el acuario (Rose Cay)'],
                    ['icon' => 'fish-outline', 'name' => 'Paseo por Haynes Cay'],
                    ['icon' => 'shield-checkmark-outline', 'name' => 'Chaleco salvavidas'],
                    ['icon' => 'leaf-outline', 'name' => 'Recorrido por manglares'],
                ],
                'recommendations' => [
                    ['icon' => 'water-outline', 'name' => 'Aquashoes'],
                    ['icon' => 'sunny-outline', 'name' => 'Bloqueador solar biodegradable'],
                    ['icon' => 'camera-outline', 'name' => 'Cámara acuática'],
                    ['icon' => 'briefcase-outline', 'name' => 'Bolsa impermeable'],
                ],
            ],
            [
                'provider' => 'vuelta-isla-chiva',
                'name' => 'Vuelta a la Isla en Chiva',
                'slug' => 'vuelta-isla-chiva',
                'subtitle' => 'La isla completa con música, playa y cultura',
                'category' => 'Tours',
                'location' => 'San Andrés Isla, Colombia',
                'duration' => '4 horas',
                'price' => 95000,
                'rating' => 4.6,
                'reviews' => 800,
                'description' => 'Recorre San Andrés en la clásica chiva del caribe con guía local y música raizal. Paradas en West View, la Piscinita, el Hoyo Soplador y la Casa Museo, con tiempo para nadar, comprar artesanías y conocer la historia de la isla.',
                'includes' => [
                    ['icon' => 'bus-outline', 'name' => 'Transporte en chiva'],
                    ['icon' => 'shield-checkmark-outline', 'name' => 'Guía local'],
                    ['icon' => 'musical-notes-outline', 'name' => 'Música y ambiente raizal'],
                    ['icon' => 'camera-outline', 'name' => 'Paradas en West View, Piscinita y el Hoyo Soplador'],
                    ['icon' => 'home-outline', 'name' => 'Visita a la Casa Museo'],
                ],
                'recommendations' => [
                    ['icon' => 'shirt-outline', 'name' => 'Ropa cómoda'],
                    ['icon' => 'sunny-outline', 'name' => 'Sombrero y bloqueador'],
                    ['icon' => 'camera-outline', 'name' => 'Cámara'],
                    ['icon' => 'cash-outline', 'name' => 'Efectivo para entradas (Morgan, West View)'],
                ],
            ],
        ];

        foreach ($tours as $item) {
            $tour = Tour::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'provider_id' => $providers[$item['provider']]->id,
                    'name' => $item['name'],
                    'subtitle' => $item['subtitle'],
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'location' => $item['location'],
                    'duration' => $item['duration'],
                    'price' => $item['price'],
                    'rating' => $item['rating'],
                    'reviews' => $item['reviews'],
                    'cover_image' => $this->photo("tours/{$item['slug']}", 'cover', $item['name'], 900, 520),
                    'status' => 'approved',
                ]
            );

            TourImage::updateOrCreate(
                ['tour_id' => $tour->id, 'order' => 0],
                ['image' => $this->photo("tours/{$item['slug']}", 'photo-1', $item['name'], 800, 600)]
            );
            TourImage::updateOrCreate(
                ['tour_id' => $tour->id, 'order' => 1],
                ['image' => $this->photo("tours/{$item['slug']}", 'photo-2', 'Mar Caribe', 800, 600)]
            );
            TourImage::updateOrCreate(
                ['tour_id' => $tour->id, 'order' => 2],
                ['image' => $this->photo("tours/{$item['slug']}", 'photo-3', 'San Andrés', 800, 600)]
            );

            foreach ($item['includes'] as $i => $include) {
                TourInclude::updateOrCreate(
                    ['tour_id' => $tour->id, 'name' => $include['name']],
                    ['icon' => $include['icon']]
                );
            }

            foreach ($item['recommendations'] as $recommendation) {
                TourRecommendation::updateOrCreate(
                    ['tour_id' => $tour->id, 'name' => $recommendation['name']],
                    ['icon' => $recommendation['icon']]
                );
            }

            Media::updateOrCreate(
                ['model_type' => Tour::class, 'model_id' => $tour->id, 'type' => 'cover', 'order' => 0],
                ['file_path' => $tour->cover_image]
            );
        }
    }

    /**
     * Real island restaurants with verified addresses, phones and menus.
     */
    private function seedRestaurants(array $providers): void
    {
        $restaurants = [
            [
                'provider' => 'la-regatta',
                'slug' => 'la-regatta',
                'name' => 'La Regatta',
                'address' => 'Avenida Newball, al lado del Club Náutico, San Andrés',
                'phone' => '+57 8 512 0437',
                'category' => 'Cocina caribeña y mariscos',
                'rating' => 4.6,
                'reviews' => 1180,
                'description' => 'Cocina caribeña frente al mar: mariscos frescos, ceviches y los sabores de la isla en un ambiente romántico con vista al atardecer sobre la bahía.',
                'opening_hours' => [
                    'monday' => ['open' => '12:00', 'close' => '23:00'],
                    'tuesday' => ['open' => '12:00', 'close' => '23:00'],
                    'wednesday' => ['open' => '12:00', 'close' => '23:00'],
                    'thursday' => ['open' => '12:00', 'close' => '23:00'],
                    'friday' => ['open' => '12:00', 'close' => '00:00'],
                    'saturday' => ['open' => '12:00', 'close' => '00:00'],
                    'sunday' => ['open' => '12:00', 'close' => '22:00'],
                ],
                'price_range' => '$$$$',
                'latitude' => 12.575400,
                'longitude' => -81.704800,
                'products' => [
                    ['name' => 'Ceviche de pescado', 'description' => 'Ceviche de pescado fresco marinado en limón, cebolla morada y ají dulce.', 'price' => 42000, 'category' => 'Entradas'],
                    ['name' => 'Langosta al ajillo', 'description' => 'Cola de langosta fresca salteada en mantequilla de ajo con arroz de coco.', 'price' => 95000, 'category' => 'Platos fuertes'],
                    ['name' => 'Arroz con mariscos', 'description' => 'Arroz caribeño con camarones, calamares, mejillones y coco.', 'price' => 75000, 'category' => 'Platos fuertes'],
                    ['name' => 'Pescado del día a la parrilla', 'description' => 'Pescado fresco del día, servido con patacones y ensalada.', 'price' => 68000, 'category' => 'Platos fuertes'],
                    ['name' => 'Tartar de atún', 'description' => 'Atún fresco en cubos con aguacate, sésamo y vinagreta de cítricos.', 'price' => 55000, 'category' => 'Entradas'],
                    ['name' => 'Mojito caribeño', 'description' => 'Ron, hierbabuena, limón y azúcar de caña.', 'price' => 25000, 'category' => 'Bebidas'],
                    ['name' => 'Piña colada', 'description' => 'Piña, coco y hielo crema.', 'price' => 22000, 'category' => 'Bebidas'],
                ],
            ],
            [
                'provider' => 'donde-francesca',
                'slug' => 'donde-francesca',
                'name' => 'Donde Francesca',
                'address' => 'Playas de San Luis (Sound Bay), San Andrés',
                'phone' => '+57 318 616 8547',
                'category' => 'Mariscos',
                'rating' => 4.1,
                'reviews' => 2397,
                'description' => 'Restaurante familiar sobre la playa de San Luis. Cazuelas de mariscos, arroz y pulpo a la parrilla con los pies en la arena y atardeceres inolvidables.',
                'opening_hours' => [
                    'monday' => ['open' => '11:00', 'close' => '17:00'],
                    'tuesday' => ['open' => '11:00', 'close' => '17:00'],
                    'wednesday' => ['open' => '11:00', 'close' => '17:00'],
                    'thursday' => ['open' => '11:00', 'close' => '17:00'],
                    'friday' => ['open' => '11:00', 'close' => '17:00'],
                    'saturday' => ['open' => '11:00', 'close' => '17:00'],
                    'sunday' => ['open' => '11:00', 'close' => '17:00'],
                ],
                'price_range' => '$$$',
                'latitude' => 12.529100,
                'longitude' => -81.731500,
                'products' => [
                    ['name' => 'Cazuela de mariscos', 'description' => 'Guiso de langosta, camarones, calamares y pescado en su punto.', 'price' => 68000, 'category' => 'Platos fuertes'],
                    ['name' => 'Arroz con mariscos', 'description' => 'Arroz de la isla con mariscos frescos y coco.', 'price' => 68000, 'category' => 'Platos fuertes'],
                    ['name' => 'Pulpo a la parrilla', 'description' => 'Pulpo tierno a la parrilla con alioli de cítricos.', 'price' => 72000, 'category' => 'Platos fuertes'],
                    ['name' => 'Camarones apanados', 'description' => 'Camarones crujientes con patacones y salsa de coco.', 'price' => 58000, 'category' => 'Platos fuertes'],
                    ['name' => 'Piña colada', 'description' => 'Clásico caribeño servido bien frío.', 'price' => 20000, 'category' => 'Bebidas'],
                ],
            ],
            [
                'provider' => 'miss-celia',
                'slug' => 'miss-celia',
                'name' => 'Restaurante Miss Celia',
                'address' => 'Avenida Newball con Avenida Colón, frente al Club Náutico, San Andrés',
                'phone' => '+57 8 513 1062',
                'category' => 'Cocina raizal',
                'rating' => 4.2,
                'reviews' => 1184,
                'description' => 'Comida típica isleña de la casa: rondón, guiso de caracol y pescado frito en un ambiente de casa raizal en pleno centro.',
                'opening_hours' => [
                    'monday' => ['open' => '11:00', 'close' => '23:00'],
                    'tuesday' => ['open' => '11:00', 'close' => '23:00'],
                    'wednesday' => ['open' => '11:00', 'close' => '23:00'],
                    'thursday' => ['open' => '11:00', 'close' => '23:00'],
                    'friday' => ['open' => '11:00', 'close' => '23:00'],
                    'saturday' => ['open' => '11:00', 'close' => '23:00'],
                    'sunday' => ['open' => '12:00', 'close' => '21:00'],
                ],
                'price_range' => '$$',
                'latitude' => 12.576800,
                'longitude' => -81.706000,
                'products' => [
                    ['name' => 'Rondón', 'description' => 'El plato emblemático de la isla: pescado, caracol, plátano y leche de coco.', 'price' => 48000, 'category' => 'Platos fuertes'],
                    ['name' => 'Guiso de caracol', 'description' => 'Caracol en guiso con cebolla, ají dulce y leche de coco.', 'price' => 52000, 'category' => 'Platos fuertes'],
                    ['name' => 'Pescado frito con patacón', 'description' => 'Pescado entero frito con patacones y arroz de coco.', 'price' => 45000, 'category' => 'Platos fuertes'],
                    ['name' => 'Pan de coco', 'description' => 'Pan tradicional horneado al carbón, ideal para acompañar.', 'price' => 4000, 'category' => 'Panes'],
                    ['name' => 'Limonada de coco', 'description' => 'Limonada cremosa con leche de coco.', 'price' => 15000, 'category' => 'Bebidas'],
                    ['name' => 'Limonada de cereza', 'description' => 'Refresco casero de cerezas tropicales.', 'price' => 15000, 'category' => 'Bebidas'],
                ],
            ],
        ];

        foreach ($restaurants as $item) {
            $restaurant = Restaurant::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'provider_id' => $providers[$item['provider']]->id,
                    'name' => $item['name'],
                    'logo' => $this->image("restaurants/{$item['slug']}", 'logo', $item['name'], 200, 200),
                    'cover_image' => $this->photo("restaurants/{$item['slug']}", 'cover', $item['name'], 900, 520),
                    'description' => $item['description'],
                    'address' => $item['address'],
                    'phone' => $item['phone'],
                    'category' => $item['category'],
                    'rating' => $item['rating'],
                    'reviews' => $item['reviews'],
                    'opening_hours' => $item['opening_hours'],
                    'price_range' => $item['price_range'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'status' => 'approved',
                ]
            );

            foreach ($item['products'] as $product) {
                RestaurantProduct::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $product['name']],
                    [
                        'description' => $product['description'],
                        'price' => $product['price'],
                        'category' => $product['category'],
                        'image' => $this->image("restaurants/{$item['slug']}/products", str($product['name'])->slug(), $product['name'], 500, 400),
                        'status' => 'active',
                    ]
                );
            }

            Media::updateOrCreate(
                ['model_type' => Restaurant::class, 'model_id' => $restaurant->id, 'type' => 'cover', 'order' => 0],
                ['file_path' => $restaurant->cover_image]
            );
        }
    }

    /**
     * Real native inns run by the local community.
     */
    private function seedInns(array $providers): void
    {
        $inns = [
            [
                'provider' => 'posada-nativa-miss-trinie',
                'slug' => 'posada-nativa-miss-trinie',
                'name' => 'Posada Nativa Miss Trinie',
                'location' => 'Avenida 20 de Julio con Carrera San Luis #4-16, San Andrés',
                'price' => 180000,
                'services' => ['Desayuno típico isleño', 'Wi-Fi', 'Aire acondicionado', 'Cocina compartida'],
                'rooms' => [
                    ['name' => 'Habitación doble', 'description' => 'Habitación con cama doble, baño privado y ventilador de techo.', 'price' => 180000, 'capacity' => 2],
                    ['name' => 'Habitación triple', 'description' => 'Habitación amplia con dos camas, A/C y baño privado.', 'price' => 220000, 'capacity' => 3],
                ],
            ],
            [
                'provider' => 'posada-nativa-atardecer-isleno',
                'slug' => 'posada-nativa-atardecer-isleno',
                'name' => 'Posada Nativa Atardecer Isleño',
                'location' => 'San Andrés Isla, Colombia',
                'price' => 220000,
                'services' => ['Desayuno incluido', 'Wi-Fi', 'Aire acondicionado', 'Transporte al aeropuerto'],
                'rooms' => [
                    ['name' => 'Habitación estándar', 'description' => 'Habitación con cama doble, A/C y baño privado.', 'price' => 220000, 'capacity' => 2],
                    ['name' => 'Suite familiar', 'description' => 'Suite con cama king, sala y balcón con vista al atardecer.', 'price' => 320000, 'capacity' => 4],
                ],
            ],
            [
                'provider' => 'posada-nativa-miss-casira',
                'slug' => 'posada-nativa-miss-casira',
                'name' => 'Posada Nativa Miss Casira',
                'location' => 'A 3 km de la playa de Cocoplum Bay, San Andrés',
                'price' => 200000,
                'services' => ['Wi-Fi', 'Aire acondicionado', 'Cocina equipada', 'Jardín tropical'],
                'rooms' => [
                    ['name' => 'Habitación doble', 'description' => 'Habitación con cama doble, A/C y baño privado.', 'price' => 180000, 'capacity' => 2],
                    ['name' => 'Habitación familiar', 'description' => 'Habitación familiar con camas, ideal para grupos.', 'price' => 200000, 'capacity' => 4],
                ],
            ],
        ];

        foreach ($inns as $item) {
            $inn = Inn::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'provider_id' => $providers[$item['provider']]->id,
                    'name' => $item['name'],
                    'description' => 'Alojamiento auténtico administrado por la comunidad raizal, a pocos pasos del mar.',
                    'cover_image' => $this->photo("inns/{$item['slug']}", 'cover', $item['name'], 900, 520),
                    'location' => $item['location'],
                    'price' => $item['price'],
                    'services' => $item['services'],
                    'availability' => true,
                    'rating' => 4.7,
                    'status' => 'approved',
                ]
            );

            foreach ($item['rooms'] as $room) {
                InnRoom::updateOrCreate(
                    ['inn_id' => $inn->id, 'name' => $room['name']],
                    [
                        'description' => $room['description'],
                        'price' => $room['price'],
                        'image' => $this->image("inns/{$item['slug']}/rooms", str($room['name'])->slug(), $room['name'], 600, 420),
                        'capacity' => $room['capacity'],
                    ]
                );
            }

            Media::updateOrCreate(
                ['model_type' => Inn::class, 'model_id' => $inn->id, 'type' => 'cover', 'order' => 0],
                ['file_path' => $inn->cover_image]
            );
        }
    }

    /**
     * Real bakeries of the island with authentic products.
     */
    private function seedBakeries(array $providers): void
    {
        $bakeries = [
            [
                'provider' => 'panaderia-suzette',
                'slug' => 'panaderia-suzette',
                'name' => 'Panadería y Repostería Suzette',
                'address' => 'Avenida Colombia, Edificio Osaka Sarie Bay, San Andrés',
                'phone' => '+57 8 512 7867',
                'category' => 'Panadería y repostería',
                'rating' => 4.6,
                'reviews' => 420,
                'description' => 'Panadería y repostería artesanal en Sarie Bay. Pan de coco, bizcochos y tortas tradicionales de la isla horneados diariamente.',
                'opening_hours' => [
                    'monday' => ['open' => '06:00', 'close' => '20:00'],
                    'tuesday' => ['open' => '06:00', 'close' => '20:00'],
                    'wednesday' => ['open' => '06:00', 'close' => '20:00'],
                    'thursday' => ['open' => '06:00', 'close' => '20:00'],
                    'friday' => ['open' => '06:00', 'close' => '20:00'],
                    'saturday' => ['open' => '06:00', 'close' => '20:00'],
                    'sunday' => ['open' => '07:00', 'close' => '18:00'],
                ],
                'price_range' => '$$',
                'latitude' => 12.580500,
                'longitude' => -81.705500,
                'products' => [
                    ['name' => 'Pan de coco', 'description' => 'El pan emblemático de la isla, horneado al carbón.', 'price' => 5000, 'category' => 'Panes'],
                    ['name' => 'Bizcocho de coco', 'description' => 'Bizcocho esponjoso de coco rallado.', 'price' => 15000, 'category' => 'Repostería'],
                    ['name' => 'Torta de fruta de pan', 'description' => 'Torta tradicional de fruta de pan, típica navideña isleña.', 'price' => 18000, 'category' => 'Postres'],
                    ['name' => 'Pan cubano', 'description' => 'Pan alargado de miga suave para desayunos.', 'price' => 6000, 'category' => 'Panes'],
                    ['name' => 'Galletas de bitansweet', 'description' => 'Galletas de hierba local, un sabor auténtico de la isla.', 'price' => 12000, 'category' => 'Repostería'],
                    ['name' => 'Croissants', 'description' => 'Croissants de mantequilla horneados cada mañana.', 'price' => 8000, 'category' => 'Repostería'],
                    ['name' => 'Jugo natural de maracuyá', 'description' => 'Jugo fresco de frutas tropicales.', 'price' => 10000, 'category' => 'Bebidas'],
                ],
            ],
            [
                'provider' => 'panaderia-trigo-dorado',
                'slug' => 'panaderia-trigo-dorado',
                'name' => 'Panadería y Repostería Trigo Dorado',
                'address' => 'Avenida Principal Shcool House, frente a Servientrega, San Andrés',
                'phone' => '+57 8 512 1227',
                'category' => 'Panadería artesanal',
                'rating' => 4.5,
                'reviews' => 350,
                'description' => 'Panadería de barrio con panes artesanales, bizcochos y empanadas saladas elaborados con recetas tradicionales.',
                'opening_hours' => [
                    'monday' => ['open' => '05:30', 'close' => '19:00'],
                    'tuesday' => ['open' => '05:30', 'close' => '19:00'],
                    'wednesday' => ['open' => '05:30', 'close' => '19:00'],
                    'thursday' => ['open' => '05:30', 'close' => '19:00'],
                    'friday' => ['open' => '05:30', 'close' => '19:00'],
                    'saturday' => ['open' => '05:30', 'close' => '19:00'],
                    'sunday' => ['closed'],
                ],
                'price_range' => '$$',
                'latitude' => 12.578800,
                'longitude' => -81.701200,
                'products' => [
                    ['name' => 'Pan de agua', 'description' => 'Pan crujiente por fuera, suave por dentro.', 'price' => 4000, 'category' => 'Panes'],
                    ['name' => 'Pan de queso', 'description' => 'Pan relleno de queso fresco, caliente cada mañana.', 'price' => 7000, 'category' => 'Panes'],
                    ['name' => 'Bizcochuelo de coco', 'description' => 'Bizcochuelo húmedo de coco con canela.', 'price' => 14000, 'category' => 'Repostería'],
                    ['name' => 'Torta de fruta de pan', 'description' => 'Torta tradicional con fruta de pan y especias.', 'price' => 18000, 'category' => 'Postres'],
                    ['name' => 'Empanadas de pescado', 'description' => 'Empanadas fritas rellenas de pescado.', 'price' => 8000, 'category' => 'Salados'],
                    ['name' => 'Galletas de avena', 'description' => 'Galletas caseras de avena con coco.', 'price' => 9000, 'category' => 'Repostería'],
                ],
            ],
            [
                'provider' => 'panaderia-susany',
                'slug' => 'panaderia-susany',
                'name' => 'Panadería y Bizcochería Susany',
                'address' => 'Avenida 20 de Julio, a una cuadra del cruce Loma San Luis, San Andrés',
                'phone' => '+57 8 512 239',
                'category' => 'Panadería y bizcochería',
                'rating' => 4.4,
                'reviews' => 280,
                'description' => 'Bizcochería tradicional en el cruce de la Loma: pan de coco artesanal, suspiros y bizcochos raizales recién horneados.',
                'opening_hours' => [
                    'monday' => ['open' => '06:00', 'close' => '19:00'],
                    'tuesday' => ['open' => '06:00', 'close' => '19:00'],
                    'wednesday' => ['open' => '06:00', 'close' => '19:00'],
                    'thursday' => ['open' => '06:00', 'close' => '19:00'],
                    'friday' => ['open' => '06:00', 'close' => '19:00'],
                    'saturday' => ['open' => '06:00', 'close' => '19:00'],
                    'sunday' => ['open' => '06:00', 'close' => '13:00'],
                ],
                'price_range' => '$',
                'latitude' => 12.574500,
                'longitude' => -81.713500,
                'products' => [
                    ['name' => 'Pan de coco artesanal', 'description' => 'Pan de coco hecho a mano al carbón.', 'price' => 4500, 'category' => 'Panes'],
                    ['name' => 'Suspiros', 'description' => 'Merengues crujientes de azúcar y coco.', 'price' => 3000, 'category' => 'Repostería'],
                    ['name' => 'Galletas de jengibre', 'description' => 'Galletas especiadas tradicionales.', 'price' => 10000, 'category' => 'Repostería'],
                    ['name' => 'Bizcocho raizal', 'description' => 'Bizcocho esponjoso con esencias de coco y vainilla.', 'price' => 16000, 'category' => 'Repostería'],
                    ['name' => 'Pan integral de coco', 'description' => 'Versión integral del pan de coco con semillas.', 'price' => 7000, 'category' => 'Panes'],
                ],
            ],
        ];

        foreach ($bakeries as $item) {
            $bakery = Bakery::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'provider_id' => $providers[$item['provider']]->id,
                    'name' => $item['name'],
                    'logo' => $this->image("bakeries/{$item['slug']}", 'logo', $item['name'], 200, 200),
                    'cover_image' => $this->photo("bakeries/{$item['slug']}", 'cover', $item['name'], 900, 520),
                    'description' => $item['description'],
                    'address' => $item['address'],
                    'phone' => $item['phone'],
                    'category' => $item['category'],
                    'rating' => $item['rating'],
                    'reviews' => $item['reviews'],
                    'opening_hours' => $item['opening_hours'],
                    'price_range' => $item['price_range'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'status' => 'approved',
                ]
            );

            foreach ($item['products'] as $product) {
                BakeryProduct::updateOrCreate(
                    ['bakery_id' => $bakery->id, 'name' => $product['name']],
                    [
                        'description' => $product['description'],
                        'price' => $product['price'],
                        'category' => $product['category'],
                        'image' => $this->image("bakeries/{$item['slug']}/products", str($product['name'])->slug(), $product['name'], 500, 400),
                        'status' => 'active',
                    ]
                );
            }

            Media::updateOrCreate(
                ['model_type' => Bakery::class, 'model_id' => $bakery->id, 'type' => 'cover', 'order' => 0],
                ['file_path' => $bakery->cover_image]
            );
        }
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'title' => 'San Andrés te espera',
                'subtitle' => 'Mar de siete colores y comunidad raizal',
                'description' => 'Descubre playas de arena blanca, el mar de siete colores y la calidez de la comunidad raizal.',
                'button_text' => 'Explorar',
                'button_url' => '/explore',
                'order' => 1,
            ],
            [
                'title' => 'Tours y cayos del Caribe',
                'subtitle' => 'Johnny Cay, Acuario y la vuelta a la isla',
                'description' => 'Navega hasta Johnny Cay, nada con mantarrayas y recorre la isla en chiva con guías locales.',
                'button_text' => 'Ver tours',
                'button_url' => '/tours',
                'order' => 2,
            ],
            [
                'title' => 'Sabores de la isla',
                'subtitle' => 'Del rondón a la langosta',
                'description' => 'La mejor gastronomía caribeña en los restaurantes frente al mar.',
                'button_text' => 'Ver restaurantes',
                'button_url' => '/restaurants',
                'order' => 3,
            ],
            [
                'title' => 'Posadas nativas auténticas',
                'subtitle' => 'Duerme donde vive la isla',
                'description' => 'Alojamientos administrados por la comunidad raizal, cerca del mar y de la cultura.',
                'button_text' => 'Ver posadas',
                'button_url' => '/inns',
                'order' => 4,
            ],
            [
                'title' => 'Panaderías y repostería isleña',
                'subtitle' => 'Pan de coco y bizcochos recién horneados',
                'description' => 'El pan de coco, los bizcochos y los suspiros que endulzan la vida en San Andrés.',
                'button_text' => 'Descubrir',
                'button_url' => '/explore',
                'order' => 5,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title']],
                [
                    'subtitle' => $banner['subtitle'],
                    'description' => $banner['description'],
                    'image' => $this->photo('banners', str($banner['title'])->slug(), $banner['title'], 1200, 640),
                    'button_text' => $banner['button_text'],
                    'button_url' => $banner['button_url'],
                    'status' => 'active',
                    'order' => $banner['order'],
                    'start_date' => now()->subDay(),
                    'end_date' => now()->addYear(),
                ]
            );
        }
    }

    private function seedPromotions(Provider $tourProvider, Provider $restaurantProvider): void
    {
        $promotions = [
            [
                'title' => '20% descuento cena para dos',
                'description' => 'Cena para dos con vista al mar y botella de bienvenida en La Regatta.',
                'discount' => 20,
                'price' => 0,
                'slug' => '20-descuento-cena-la-regatta',
                'provider' => $restaurantProvider,
            ],
            [
                'title' => 'Tour Johnny Cay temporada baja',
                'description' => 'Viaje al cayo más bello del Caribe con transporte y guía local.',
                'discount' => 15,
                'price' => 102000,
                'slug' => 'tour-johnny-cay-temporada-baja',
                'provider' => $tourProvider,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::updateOrCreate(
                ['title' => $promotion['title']],
                [
                    'provider_id' => $promotion['provider']->id,
                    'description' => $promotion['description'],
                    'image' => $this->image('promotions', $promotion['slug'], $promotion['discount'].'% OFF', 900, 520),
                    'discount' => $promotion['discount'],
                    'price' => $promotion['price'] ?: null,
                    'start_date' => now()->subDay(),
                    'end_date' => now()->addMonths(3),
                    'status' => 'active',
                ]
            );
        }
    }

    /**
     * Noticias de arranque para la pantalla Noticias.
     *
     * Cada fila cubre un `kind` distinto (evento, consejo, noticia, promoción)
     * porque la tarjeta los pinta con etiquetas diferentes y un feed entero de
     * una sola clase no ejercita ese diseño.
     *
     * Una queda en `draft` y otra con `published_at` en el futuro a propósito:
     * son los dos casos que la pantalla de Noticias tiene que filtrar, y
     * comprobarlos a ojo en la app es más rápido que montar un test para ello.
     * El servicio público ya los descarta por `scopePublished()`.
     */
    private function seedNews(Provider $tourProvider, Provider $innProvider): void
    {
        $news = [
            [
                'title' => 'Vuelve el Festival de la Arepa de Huevo',
                'slug' => 'festival-arepa-huevo',
                'kind' => News::KIND_EVENT,
                'excerpt' => 'Tres días de música en vivo y arepas de huevo por toda la isla.',
                'body' => "El Festival de la Arepa de Huevo vuelve a la plaza.\nLas orquestas tocarán en vivo y los puestos de comida abrirán desde las nueve de la mañana.",
                'link_url' => null,
                'provider' => null,
                'is_featured' => true,
                'order' => 1,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Cómo moverse por la isla sin gastar de más',
                'slug' => 'como-moverse-por-la-isla',
                'kind' => News::KIND_TIP,
                'excerpt' => 'Chivas compartidas, taxis de tarifa fija y el bus del circuito.',
                'body' => "La chiva compartida y el bus del circuito son lo más barato para recorrer la isla.\nPara el centro, los taxis de tarifa fija salen mejor que ir a cuenta.",
                'link_url' => '/explore',
                'provider' => null,
                'is_featured' => true,
                'order' => 2,
                'published_at' => now()->subDays(6),
            ],
            [
                'title' => 'Exploria abre salidas a Johnny Cay todos los días',
                'slug' => 'exploria-johnny-cay-diario',
                'kind' => News::KIND_NEWS,
                'excerpt' => 'Más salidas al cayo y salidas en español e inglés.',
                'body' => 'El operador amplía su calendario de salidas al cayo más visitado del Caribe.',
                'link_url' => '/tours/johnny-cay',
                'provider' => $tourProvider,
                'is_featured' => false,
                'order' => 3,
                'published_at' => now()->subDays(11),
            ],
            [
                'title' => 'Noche de música en la Posada Miss Trinie',
                'slug' => 'musica-miss-trinie',
                'kind' => News::KIND_PROMOTION,
                'excerpt' => 'Guitarra en vivo y 20% de descuento al reservar desde la app.',
                'body' => 'Una noche de música en vivo con 20% de descuento directo en la posada.',
                'link_url' => '/inns/posada-nativa-miss-trinie',
                'provider' => $innProvider,
                'is_featured' => false,
                'order' => 4,
                'published_at' => now()->subDays(15),
            ],
        ];

        foreach ($news as $item) {
            News::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => $item['body'],
                    'image' => $this->photo('news', $item['slug'], $item['title'], 1200, 720),
                    'kind' => $item['kind'],
                    'provider_id' => $item['provider']?->id,
                    'link_url' => $item['link_url'],
                    'status' => News::STATUS_PUBLISHED,
                    'is_featured' => $item['is_featured'],
                    'order' => $item['order'],
                    'published_at' => $item['published_at'],
                ]
            );
        }

        // Borrador: existe en la tabla y no debe salir en el feed.
        News::updateOrCreate(
            ['slug' => 'borrador-nota-interna'],
            [
                'title' => 'Nota interna sin publicar',
                'excerpt' => 'Comprobar que el feed público no la devuelve.',
                'body' => 'Esta nota esta en borrador.',
                'image' => $this->image('news', 'borrador-nota-interna', 'Borrador', 1200, 720),
                'kind' => News::KIND_NEWS,
                'status' => News::STATUS_DRAFT,
                'is_featured' => false,
                'order' => 99,
            ]
        );
    }

    /**
     * Returns a real photo stored under `storage/app/public/...` when it
     * exists (downloaded by the companion `scripts/import-media.ps1`) or
     * falls back to a branded SVG. Guarantees every media URL resolves.
     */
    private function photo(string $dir, string $name, string $label, int $width, int $height): string
    {
        $safe = str($name)->slug();
        $path = "{$dir}/{$safe}.jpg";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        return $this->image($dir, $safe, $label, $width, $height);
    }

    /**
     * Writes a minimal branded SVG into public storage and returns its
     * relative path (`tours/johnny-cay/cover.svg`).
     */
    private function image(string $dir, string $name, string $label, int $width = 800, int $height = 500): string
    {
        $safe = str($name)->slug();
        $path = "{$dir}/{$safe}.svg";
        $label = str($label)->limit(28);

        $colors = ['#0077B6', '#00D1C5', '#FF7A59', '#1F8A5C', '#FF9E45'];
        $color = $colors[crc32($path) % count($colors)];

        $r1 = (int) round($height * 0.5);
        $r2 = (int) round($height * 0.4);
        $titleSize = (int) round($height * 0.11);
        $subtitleSize = (int) round($height * 0.045);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
          <defs>
            <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$color}" stop-opacity="0.9"/>
              <stop offset="1" stop-color="#0B2D4A" stop-opacity="0.95"/>
            </linearGradient>
          </defs>
          <rect width="{$width}" height="{$height}" fill="url(#g)"/>
          <circle cx="{$width}" cy="0" r="{$r1}" fill="#FFFFFF" opacity="0.12"/>
          <circle cx="0" cy="{$height}" r="{$r2}" fill="#FFFFFF" opacity="0.10"/>
          <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="{$titleSize}" font-weight="bold" fill="#FFFFFF" text-anchor="middle" dominant-baseline="middle">{$label}</text>
          <text x="50%" y="62%" font-family="Arial, sans-serif" font-size="{$subtitleSize}" fill="#FFFFFF" opacity="0.85" text-anchor="middle">SEVEN · Más que un destino</text>
        </svg>
        SVG;

        Storage::disk('public')->put($path, trim($svg));

        return $path;
    }
}
