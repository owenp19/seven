<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Experience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DemoDataSeeder
 *
 * Populates the database with a small, curated set of approval
 * businesses, native inns, experiences and a few example bookings so
 * the app can be explored before real operators start onboarding.
 *
 * Run with: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const ISLAND_LAT = 12.5847;

    private const ISLAND_LNG = -81.7005;

    public function run(): void
    {
        $restaurantes = Category::where('slug', 'restaurantes')->first();
        $comidaRapida = Category::where('slug', 'comida-rapida')->first() ?? $restaurantes;
        $panaderias = Category::where('slug', 'panaderias')->first();
        $cocinaRaizal = Category::where('slug', 'cocina-raizal')->first() ?? $restaurantes;
        $posadas = Category::where('slug', 'posadas-nativas')->first();
        $experiencias = Category::where('slug', 'experiencias')->first() ?? Category::where('slug', 'tours')->first();

        $this->seedBusinesses($restaurantes, $comidaRapida, $panaderias, $cocinaRaizal);
        $this->seedAccommodations($posadas);
        $this->seedExperiences($experiencias);
        $this->seedBookings();
    }

    private function seedBusinesses($restaurantes, $comidaRapida, $panaderias, $cocinaRaizal): void
    {
        $openHours = [
            'monday' => ['open' => '10:00', 'close' => '22:00'],
            'tuesday' => ['open' => '10:00', 'close' => '22:00'],
            'wednesday' => ['open' => '10:00', 'close' => '22:00'],
            'thursday' => ['open' => '10:00', 'close' => '22:00'],
            'friday' => ['open' => '10:00', 'close' => '23:00'],
            'saturday' => ['open' => '10:00', 'close' => '23:00'],
            'sunday' => ['open' => '11:00', 'close' => '21:00'],
        ];

        $businesses = [
            [
                'category' => $restaurantes, 'name' => 'Restaurante El Rincón Isleño', 'phone' => '+57 318 555 0142',
                'description' => 'Cocina caribeña con mariscos frescos del día y ambiente familiar frente al mar.',
                'tags' => ['mariscos', 'familiar'], 'features' => ['wifi', 'terraza'],
            ],
            [
                'category' => $restaurantes, 'name' => 'Mar y Coco', 'phone' => '+57 318 555 0143',
                'description' => 'Especialidades del mar en un espacio abierto con vista al mar.',
                'tags' => ['mariscos', 'romántico'], 'features' => ['wifi', 'terraza', 'pet-friend'],
            ],
            [
                'category' => $cocinaRaizal, 'name' => 'Sazón Raizal de la Abuela', 'phone' => '+57 318 555 0144',
                'description' => 'Platos tradicionales raizales: rondón, pescado frito y pan de coco.',
                'tags' => ['rondón', 'cocina tradicional'], 'features' => ['familiar', 'reservas'],
            ],
            [
                'category' => $cocinaRaizal, 'name' => 'La Casa del Rondón', 'phone' => '+57 318 555 0145',
                'description' => 'El rondón auténtico de la isla, cocinado a fuego lento como se hace en casa.',
                'tags' => ['rondón', 'típico'], 'features' => ['familiar'],
            ],
            [
                'category' => $comidaRapida, 'name' => 'Fish Burger Isleña', 'phone' => '+57 318 555 0146',
                'description' => 'Las mejores hamburguesas de pescado y frutos de mar de la isla.',
                'tags' => ['hamburguesas', 'pescado'], 'features' => ['wifi', 'delivery'],
            ],
            [
                'category' => $comidaRapida, 'name' => 'Arepas & Coco Street', 'phone' => '+57 318 555 0147',
                'description' => 'Arepas de huevo, coco y opciones rápidas para probar el sabor caribeño.',
                'tags' => ['arepas', 'street food'], 'features' => ['delivery'],
            ],
            [
                'category' => $panaderias, 'name' => 'Panadería Dulce Coco', 'phone' => '+57 318 555 0148',
                'description' => 'Pan de coco artesanal, repostería isleña y café de la región.',
                'tags' => ['pan de coco', 'repostería'], 'features' => ['wifi'],
            ],
            [
                'category' => $panaderias, 'name' => 'La Isleña Bakehouse', 'phone' => '+57 318 555 0149',
                'description' => 'Panadería artesanal con recetas auténticas de la isla y desayunos tradicionales.',
                'tags' => ['artesanal', 'desayunos'], 'features' => ['wifi', 'terraza'],
            ],
            [
                'category' => $restaurantes, 'name' => 'Frutos del Mar', 'phone' => '+57 318 555 0150',
                'description' => 'Ceviches, langosta y pescados servidos en un rincón con encanto caribeño.',
                'tags' => ['ceviche', 'mariscos'], 'features' => ['wifi', 'reservas'],
            ],
            [
                'category' => $cocinaRaizal, 'name' => 'Kokis & Rondón', 'phone' => '+57 318 555 0151',
                'description' => 'Cocina raizal contemporánea que mezcla lo tradicional con toques modernos.',
                'tags' => ['típico', 'moderno'], 'features' => ['wifi', 'terraza'],
            ],
        ];

        foreach ($businesses as $i => $item) {
            Business::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $item['category']->id,
                    'slug' => Str::slug($item['name']).'-'.($i + 1),
                    'description' => $item['description'],
                    'address' => 'San Andrés Isla, Centro',
                    'latitude' => self::ISLAND_LAT + fake()->randomFloat(5, -0.02, 0.02),
                    'longitude' => self::ISLAND_LNG + fake()->randomFloat(5, -0.02, 0.02),
                    'phone' => $item['phone'],
                    'whatsapp' => '+57'.preg_replace('/\D+/', '', $item['phone']),
                    'email' => Str::slug($item['name']).'@seven.test',
                    'images' => [
                        "https://picsum.photos/seed/biz-{$i}-1/800/600",
                        "https://picsum.photos/seed/biz-{$i}-2/800/600",
                    ],
                    'opening_hours' => $openHours,
                    'tags' => $item['tags'],
                    'features' => $item['features'],
                    'is_verified' => true,
                    'status' => 'approved',
                ]
            );
        }
    }

    private function seedAccommodations($posadas): void
    {
        $accommodations = [
            ['name' => 'Posada El Coco Loco', 'capacity' => 4, 'price' => 280000, 'services' => ['Desayuno incluido', 'Wi-Fi', 'Aire acondicionado']],
            ['name' => 'Casa Raizal San Andrés', 'capacity' => 3, 'price' => 220000, 'services' => ['Desayuno incluido', 'Wi-Fi']],
            ['name' => 'Posada Atardecer Isleño', 'capacity' => 5, 'price' => 320000, 'services' => ['Desayuno incluido', 'Wi-Fi', 'Aire acondicionado', 'Piscina']],
            ['name' => 'Cabaña Mar Caribe', 'capacity' => 2, 'price' => 190000, 'services' => ['Wi-Fi', 'Aire acondicionado', 'Toallas']],
            ['name' => 'Posada La Almendra', 'capacity' => 6, 'price' => 350000, 'services' => ['Desayuno incluido', 'Wi-Fi', 'Aire acondicionado', 'Transporte al aeropuerto']],
            ['name' => 'Hostal Raíz', 'capacity' => 3, 'price' => 150000, 'services' => ['Desayuno incluido', 'Wi-Fi', 'Cocina compartida']],
        ];

        foreach ($accommodations as $i => $item) {
            Accommodation::updateOrCreate(
                ['name' => $item['name']],
                [
                    'slug' => Str::slug($item['name']).'-'.($i + 1),
                    'description' => 'Alojamiento auténtico administrado por la comunidad raizal, a pocos pasos del mar.',
                    'capacity' => $item['capacity'],
                    'price_per_night' => $item['price'],
                    'currency' => 'COP',
                    'address' => 'San Andrés Isla, sector norte',
                    'latitude' => self::ISLAND_LAT + fake()->randomFloat(5, 0.00, 0.03),
                    'longitude' => self::ISLAND_LNG + fake()->randomFloat(5, 0.00, 0.03),
                    'images' => [
                        "https://picsum.photos/seed/acc-{$i}-1/800/600",
                        "https://picsum.photos/seed/acc-{$i}-2/800/600",
                    ],
                    'included_services' => $item['services'],
                    'status' => 'approved',
                ]
            );
        }
    }

    private function seedExperiences($experiencias): void
    {
        $experiences = [
            ['name' => 'Tour en lancha por los cayos', 'duration' => 240, 'price' => 180000, 'capacity' => 8, 'includes' => ['Guía local', 'Snorkel', 'Transporte', 'Agua']],
            ['name' => 'Clase de cocina raizal', 'duration' => 90, 'price' => 95000, 'capacity' => 6, 'includes' => ['Ingredientes', 'Recetas tradicionales', 'Degustación']],
            ['name' => 'Atardecer en kayak', 'duration' => 120, 'price' => 120000, 'capacity' => 4, 'includes' => ['Kayak', 'Chaleco salvavidas', 'Guía']],
            ['name' => 'Ruta histórica de la isla', 'duration' => 150, 'price' => 75000, 'capacity' => 10, 'includes' => ['Guía local', 'Entradas', 'Transporte']],
            ['name' => 'Buceo de bautismo', 'duration' => 180, 'price' => 320000, 'capacity' => 2, 'includes' => ['Equipo completo', 'Instructor certificado']],
            ['name' => 'Tour de café y coco', 'duration' => 120, 'price' => 85000, 'capacity' => 8, 'includes' => ['Degustación', 'Guía']],
        ];

        foreach ($experiences as $i => $item) {
            Experience::updateOrCreate(
                ['name' => $item['name']],
                [
                    'slug' => Str::slug($item['name']).'-'.($i + 1),
                    'description' => 'Vive la isla desde adentro con una experiencia auténtica dirigida por la comunidad local.',
                    'category_id' => $experiencias->id,
                    'duration_minutes' => $item['duration'],
                    'price_per_person' => $item['price'],
                    'currency' => 'COP',
                    'max_capacity' => $item['capacity'],
                    'address' => 'San Andrés Isla',
                    'latitude' => self::ISLAND_LAT + fake()->randomFloat(5, -0.01, 0.01),
                    'longitude' => self::ISLAND_LNG + fake()->randomFloat(5, -0.01, 0.01),
                    'includes' => $item['includes'],
                    'images' => ["https://picsum.photos/seed/exp-{$i}-1/800/600"],
                    'status' => 'approved',
                ]
            );
        }
    }

    private function seedBookings(): void
    {
        $customers = [
            ['name' => 'Laura Gómez', 'phone' => '+57 300 210 3344', 'email' => 'laura.gomez@mail.test'],
            ['name' => 'Carlos Peña', 'phone' => '+57 300 210 3345', 'email' => 'carlos.pena@mail.test'],
            ['name' => 'Mariana Ruiz', 'phone' => '+57 300 210 3346', 'email' => null],
            ['name' => 'Andrés Suárez', 'phone' => '+57 300 210 3347', 'email' => 'andres.suarez@mail.test'],
        ];

        $customerIds = [];
        foreach ($customers as $customer) {
            $customerIds[] = Customer::updateOrCreate(
                ['phone' => $customer['phone']],
                $customer
            )->id;
        }

        $business = Business::where('name', 'Restaurante El Rincón Isleño')->first();
        $accommodation = Accommodation::where('name', 'Posada El Coco Loco')->first();
        $experience = Experience::where('name', 'Tour en lancha por los cayos')->first();

        $bookings = [
            [
                'customer_id' => $customerIds[0],
                'bookable' => $business,
                'type' => 'gastronomic',
                'start_at' => now()->addDays(2)->setTime(19, 0),
                'end_at' => null,
                'guests' => 2,
                'status' => 'confirmed',
                'total_amount' => 0,
                'special_requests' => 'Mesa con vista al mar',
            ],
            [
                'customer_id' => $customerIds[1],
                'bookable' => $accommodation,
                'type' => 'accommodation',
                'start_at' => now()->addDays(5)->setTime(15, 0),
                'end_at' => now()->addDays(7)->setTime(12, 0),
                'guests' => 3,
                'status' => 'pending',
                'total_amount' => 280000 * 2,
                'special_requests' => null,
            ],
            [
                'customer_id' => $customerIds[2],
                'bookable' => $experience,
                'type' => 'experience',
                'start_at' => now()->addDays(3)->setTime(9, 0),
                'end_at' => null,
                'guests' => 4,
                'status' => 'confirmed',
                'total_amount' => 180000 * 4,
                'special_requests' => null,
            ],
        ];

        foreach ($bookings as $booking) {
            Booking::updateOrCreate(
                ['start_at' => $booking['start_at'], 'customer_id' => $booking['customer_id']],
                [
                    'booking_code' => 'SEV-'.strtoupper(Str::random(6)),
                    'bookable_type' => $booking['bookable']::class,
                    'bookable_id' => $booking['bookable']->id,
                    'type' => $booking['type'],
                    'end_at' => $booking['end_at'],
                    'guests' => $booking['guests'],
                    'total_amount' => $booking['total_amount'],
                    'currency' => 'COP',
                    'special_requests' => $booking['special_requests'],
                    'status' => $booking['status'],
                ]
            );
        }
    }
}
