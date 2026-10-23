import { describe, expect, it } from 'vitest';

import { Banner } from './banner.model';
import type { BannerPayload } from './banner.model';
import { Category } from './category.model';
import { Business } from './business.model';
import type { BusinessPayload } from './business.model';
import type { CategoryPayload } from './category.model';
import { Inn } from './inn.model';
import type { InnPayload } from './inn.model';
import { Promotion } from './promotion.model';
import type { PromotionPayload } from './promotion.model';
import { Restaurant } from './restaurant.model';
import type { RestaurantPayload } from './restaurant.model';
import { Tour } from './tour.model';
import type { TourPayload } from './tour.model';
import { AssistantReply } from './assistant.model';
import type { AssistantReplyPayload } from './assistant.model';

describe('Category.fromApi', () => {
  it('maps the API payload into a Category instance', () => {
    const payload: CategoryPayload = {
      id: 1,
      slug: 'restaurantes',
      name: 'Restaurantes',
      icon: 'restaurant-outline',
      image: null,
      description: 'Gastronomía local',
      sort_order: 2,
      business_count: 14,
    };

    const category = Category.fromApi(payload);

    expect(category).toBeInstanceOf(Category);
    expect(category.slug).toBe('restaurantes');
    expect(category.sortOrder).toBe(2);
    expect(category.businessCount).toBe(14);
  });

  it('defaults optional fields to safe values', () => {
    const category = Category.fromApi({ id: 3, slug: 'panaderias', name: 'Panaderías' });

    expect(category.icon).toBeNull();
    expect(category.sortOrder).toBe(0);
    expect(category.businessCount).toBe(0);
  });
});

describe('Business.fromApi', () => {
  const payload: BusinessPayload = {
    id: 1,
    slug: 'restaurante-el-rincon',
    name: 'Restaurante El Rincón',
    description: 'Cocina caribeña',
    category: Category.fromApi({ id: 1, slug: 'restaurantes', name: 'Restaurantes' }),
    address: 'Centro',
    location: { latitude: '12.58', longitude: '-81.70' },
    phone: '+57 318 555 0142',
    whatsapp: '+573185550142',
    email: 'rincon@seven.test',
    images: ['https://img/a.jpg', 'https://img/b.jpg'],
    opening_hours: { monday: { open: '10:00', close: '22:00' } },
    tags: ['mariscos'],
    features: ['wifi'],
    is_verified: true,
    rating_avg: 4.5,
    rating_count: 12,
  };

  it('maps the API payload into a Business instance', () => {
    const business = Business.fromApi(payload);

    expect(business).toBeInstanceOf(Business);
    expect(business.name).toBe('Restaurante El Rincón');
    expect(business.isVerified).toBe(true);
    expect(business.ratingAvg).toBe(4.5);
    expect(business.category?.slug).toBe('restaurantes');
  });

  it('exposes the first image as the cover', () => {
    const business = Business.fromApi(payload);
    expect(business.coverImage).toBe('https://img/a.jpg');
  });

  it('returns null cover when there are no images', () => {
    const empty = Business.fromApi({ ...payload, images: [] });
    expect(empty.coverImage).toBeNull();
  });
});

describe('Tour.fromApi', () => {
  const payload: TourPayload = {
    id: 1,
    slug: 'johnny-cay',
    name: 'Johnny Cay',
    subtitle: 'Un paraíso a pocos minutos',
    description: 'Una isla de ensueño.',
    category: 'Tours',
    location: 'San Andrés, Colombia',
    duration: '4-5 horas',
    price: 170000,
    rating: 4.8,
    reviews: 132,
    cover_image: 'https://img/cover.webp',
    status: 'approved',
    images: [
      { id: 1, image: 'https://img/a.webp', order: 1 },
      { id: 2, image: 'https://img/b.webp', order: 2 },
    ],
    includes: [{ icon: 'boat-outline', name: 'Transporte' }],
    recommendations: [{ icon: 'sunny-outline', name: 'Bloqueador solar' }],
  };

  it('maps the API payload into a Tour instance', () => {
    const tour = Tour.fromApi(payload);

    expect(tour).toBeInstanceOf(Tour);
    expect(tour.slug).toBe('johnny-cay');
    expect(tour.name).toBe('Johnny Cay');
    expect(tour.rating).toBe(4.8);
    expect(tour.reviews).toBe(132);
    expect(tour.category).toBe('Tours');
  });

  it('exposes gallery, counter and place from the relationships', () => {
    const tour = Tour.fromApi(payload);

    expect(tour.gallery).toEqual(['https://img/a.webp', 'https://img/b.webp']);
    expect(tour.photos).toBe(2);
    expect(tour.place).toBe('San Andrés, Colombia');
  });

  it('derives the detail features from duration, category and location', () => {
    const tour = Tour.fromApi(payload);

    expect(tour.features).toHaveLength(3);
    expect(tour.features[0]).toEqual({ icon: 'time-outline', value: '4-5 horas', label: 'Duración' });
    expect(tour.features[2]).toEqual({ icon: 'location-outline', value: 'San Andrés', label: 'Ubicación' });
  });

  it('defaults optional fields to safe values', () => {
    const tour = Tour.fromApi({ id: 2, slug: 'tour', name: 'Tour', price: 0, cover_image: 'cover', status: 'approved' });

    expect(tour.photos).toBe(0);
    expect(tour.features).toEqual([]);
    expect(tour.includes).toEqual([]);
    expect(tour.recommendations).toEqual([]);
    expect(tour.category).toBeNull();
  });

  it('keeps the operator name so the card can show who runs the tour', () => {
    const tour = Tour.fromApi({ ...payload, provider: { id: 7, business_name: 'Sea Keys Tours' } });

    expect(tour.providerName).toBe('Sea Keys Tours');
  });

  it('falls back to the owner name and then to an empty string', () => {
    const ownerOnly = Tour.fromApi({ ...payload, provider: { id: 7, business_name: '  ', owner_name: 'Ana Ruiz' } });
    const withoutProvider = Tour.fromApi({ ...payload, provider: null });

    expect(ownerOnly.providerName).toBe('Ana Ruiz');
    expect(withoutProvider.providerName).toBe('');
  });
});

describe('Banner.fromApi', () => {
  it('maps the API payload into a Banner instance', () => {
    const payload: BannerPayload = {
      id: 1,
      title: 'San Andrés te espera',
      subtitle: 'Vive el Caribe',
      description: 'Una isla única.',
      image: 'https://img/banner.webp',
      button_text: 'Ver tours',
      button_url: '/tours',
      status: 'active',
      order: 1,
      start_date: null,
      end_date: null,
    };

    const banner = Banner.fromApi(payload);

    expect(banner).toBeInstanceOf(Banner);
    expect(banner.title).toBe('San Andrés te espera');
    expect(banner.buttonText).toBe('Ver tours');
    expect(banner.buttonUrl).toBe('/tours');
  });
});

describe('Promotion.fromApi', () => {
  it('maps the API payload into a Promotion instance', () => {
    const payload: PromotionPayload = {
      id: 1,
      provider_id: 1,
      title: '20% descuento cena romántica',
      description: 'Cena para dos.',
      image: 'https://img/promo.webp',
      discount: 20,
      price: 200000,
      start_date: null,
      end_date: '2026-12-31',
      status: 'active',
      provider: { id: 1, business_name: 'La Regatta', type: 'restaurant', status: 'approved', verification_status: 'verified' },
    };

    const promotion = Promotion.fromApi(payload);

    expect(promotion).toBeInstanceOf(Promotion);
    expect(promotion.discount).toBe(20);
    expect(promotion.providerName).toBe('La Regatta');
    expect(promotion.endDate).toBe('2026-12-31');
  });
});

describe('Restaurant.fromApi', () => {
  it('maps the API payload into a Restaurant instance with products', () => {
    const payload: RestaurantPayload = {
      id: 1,
      slug: 'la-regatta',
      name: 'La Regatta',
      logo: null,
      cover_image: 'https://img/cover.webp',
      description: 'Cocina caribeña.',
      address: 'San Andrés',
      phone: '+57 318 555 0142',
      category: 'Cocina caribeña',
      rating: 4.6,
      reviews: 88,
      opening_hours: null,
      price_range: '$$',
      location: { latitude: 12.584, longitude: -81.7 },
      status: 'approved',
      products: [
        { id: 1, name: 'Langosta al ajillo', description: 'Servida con arroz de coco.', price: 95000, image: 'https://img/langosta.webp', category: 'Platos fuertes', status: 'active' },
      ],
    };

    const restaurant = Restaurant.fromApi(payload);

    expect(restaurant).toBeInstanceOf(Restaurant);
    expect(restaurant.slug).toBe('la-regatta');
    expect(restaurant.products).toHaveLength(1);
    expect(restaurant.products[0].price).toBe(95000);
    expect(restaurant.cover).toBe('https://img/cover.webp');
  });

  it('falls back to the logo as cover when there is no cover image', () => {
    const restaurant = Restaurant.fromApi({
      id: 1,
      slug: 'r',
      name: 'R',
      logo: 'https://img/logo.webp',
      cover_image: null,
      description: null,
      address: null,
      phone: null,
      category: null,
      rating: 0,
      reviews: 0,
      opening_hours: null,
      price_range: null,
      location: { latitude: 0, longitude: 0 },
      status: 'approved',
    });

    expect(restaurant.cover).toBe('https://img/logo.webp');
  });

  it('keeps the operator name when the API sends the provider', () => {
    const restaurant = Restaurant.fromApi({
      id: 1,
      slug: 'la-regatta',
      name: 'La Regatta',
      logo: null,
      cover_image: null,
      description: null,
      address: null,
      phone: null,
      category: null,
      rating: 0,
      reviews: 0,
      opening_hours: null,
      price_range: null,
      location: { latitude: 0, longitude: 0 },
      status: 'approved',
      provider: { id: 3, business_name: 'La Regatta S.A.S.' },
    });

    expect(restaurant.providerName).toBe('La Regatta S.A.S.');
  });
});

describe('Inn.fromApi', () => {
  it('maps the API payload into an Inn instance with rooms', () => {
    const payload: InnPayload = {
      id: 1,
      slug: 'posada-el-coco-loco',
      name: 'Posada El Coco Loco',
      description: 'Frente al mar.',
      cover_image: 'https://img/inn.webp',
      location: 'San Luis',
      price: 280000,
      services: ['desayuno incluido', 'wifi'],
      availability: true,
      rating: 4.7,
      status: 'approved',
      rooms: [{ id: 1, name: 'Habitación doble', description: 'Balcón al mar.', price: 280000, image: 'https://img/room.webp', capacity: 2 }],
    };

    const inn = Inn.fromApi(payload);

    expect(inn).toBeInstanceOf(Inn);
    expect(inn.rooms).toHaveLength(1);
    expect(inn.rooms[0].capacity).toBe(2);
    expect(inn.availability).toBe(true);
  });

  it('keeps the operator name and leaves it empty without a provider', () => {
    const base: InnPayload = {
      id: 1,
      slug: 'posada-el-coco-loco',
      name: 'Posada El Coco Loco',
      description: null,
      cover_image: null,
      location: null,
      price: 280000,
      services: [],
      availability: true,
      rating: 4.7,
      status: 'approved',
    };

    expect(Inn.fromApi({ ...base, provider: { id: 9, business_name: 'Familia Moreno' } }).providerName).toBe(
      'Familia Moreno',
    );
    expect(Inn.fromApi({ ...base, provider: null }).providerName).toBe('');
    expect(Inn.fromApi(base).providerName).toBe('');
  });
});

describe('AssistantReply.fromApi', () => {
  const replyPayload: AssistantReplyPayload = {
    conversation_id: '01JABCDEF0123456789ABCDEFG',
    message: 'Con $500.000 te propongo estas opciones.',
    recommendations: [
      {
        type: 'tour',
        id: '12',
        name: 'Johnny Cay',
        summary: 'Un paraíso a pocos minutos',
        price: 170000,
        price_unit: 'per_person',
        rating: 4.8,
        review_count: 132,
        location: 'San Andrés',
        image: 'https://img/johnny.webp',
        route: ['/tour', 'johnny-cay'],
        provider: 'Sea Keys Tours',
        availability: 'available',
      },
      {
        type: 'restaurant',
        id: '7',
        name: 'La Regatta',
        summary: null,
        price: null,
        price_unit: null,
        rating: 4.6,
        review_count: 88,
        location: null,
        image: null,
        route: ['/restaurant', 'la-regatta'],
        provider: null,
        availability: 'unknown',
      },
    ],
    packages: [
      {
        total: 340000,
        remaining: 160000,
        items: [
          {
            type: 'tour',
            id: '12',
            name: 'Johnny Cay',
            summary: null,
            price: 170000,
            price_unit: 'per_person',
            rating: 4.8,
            review_count: 132,
            location: 'San Andrés',
            image: null,
            route: ['/tour', 'johnny-cay'],
            provider: 'Sea Keys Tours',
            availability: 'available',
          },
        ],
      },
    ],
    meta: { provider: 'gemini', fallback: false, iterations: 2 },
  };

  it('maps the reply, recommendations and packages', () => {
    const reply = AssistantReply.fromApi(replyPayload);

    expect(reply).toBeInstanceOf(AssistantReply);
    expect(reply.conversationId).toBe('01JABCDEF0123456789ABCDEFG');
    expect(reply.recommendations).toHaveLength(2);
    expect(reply.recommendations[0].hasPrice).toBe(true);
    expect(reply.recommendations[0].key).toBe('tour:12');
    expect(reply.packages[0].total).toBe(340000);
    expect(reply.packages[0].items[0].name).toBe('Johnny Cay');
    expect(reply.meta.fallback).toBe(false);
  });

  it('keeps the restaurant without a scalar price and flags it', () => {
    const reply = AssistantReply.fromApi(replyPayload);
    const restaurant = reply.recommendations[1];

    expect(restaurant.hasPrice).toBe(false);
    expect(restaurant.priceUnit).toBeNull();
    expect(restaurant.availability).toBe('unknown');
  });

  it('drops an empty route and unknown types to safe values', () => {
    const reply = AssistantReply.fromApi({
      conversation_id: 'x',
      message: 'hola',
      recommendations: [
        {
          type: 'unknown-vertical',
          id: '1',
          name: 'Algo',
          summary: null,
          price: 0,
          price_unit: 'per_hour',
          rating: null,
          review_count: null,
          location: null,
          image: null,
          route: [],
          provider: null,
          availability: 'weird',
        },
      ],
      packages: [],
      meta: { provider: 'fake', fallback: true, iterations: 1 },
    });

    const item = reply.recommendations[0];
    expect(item.type).toBe('business');
    expect(item.route).toBeNull();
    expect(item.priceUnit).toBeNull();
    expect(item.availability).toBe('unknown');
    expect(item.hasPrice).toBe(false);
  });
});