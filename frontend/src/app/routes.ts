import { Routes } from '@angular/router';

import { featureFlagGuard } from './guards/feature-flag.guard';

/**
 * SEVEN route tree.
 *
 * - The tabbed shell (`tabs`) hosts the main browsing areas: Home,
 *   Explore, Seven AI (the assistant), Bookings and Contact (the hub).
 *   Detail pages are pushed on top of the stack without tabs.
 * - Every page is a lazy-loaded standalone component.
 */
export const routes: Routes = [
  {
    path: 'welcome',
    loadComponent: () => import('./features/welcome/welcome.page').then((m) => m.WelcomePage),
    data: { title: 'Bienvenido', preload: true },
  },

  {
    path: '',
    redirectTo: '/welcome',
    pathMatch: 'full',
  },

  {
    path: '',
    loadComponent: () => import('./layouts/tabs/tabs-layout.component').then((m) => m.TabsLayoutComponent),
    data: { preload: true },
    children: [
      {
        path: 'home',
        loadComponent: () => import('./features/home/home.page').then((m) => m.HomePage),
        data: { preload: true },
      },
      {
        path: 'explore',
        loadComponent: () => import('./features/explore/explore.page').then((m) => m.ExplorePage),
        data: { preload: true },
      },
      {
        path: 'bookings',
        loadComponent: () => import('./features/bookings/bookings.page').then((m) => m.BookingsPage),
        data: { preload: true },
      },
      {
        path: 'assistant',
        loadComponent: () => import('./features/assistant/assistant.page').then((m) => m.AssistantPage),
        data: { title: 'Asistente Seven' },
      },
      {
        path: 'saved',
        loadComponent: () => import('./features/saved/saved.page').then((m) => m.SavedPage),
        data: { title: 'Guardados' },
      },
      {
        path: 'contact',
        loadComponent: () => import('./features/contact/contact.page').then((m) => m.ContactPage),
        data: { title: 'Contacto' },
      },
    ],
  },

  /** Detail pages pushed above the tab stack. */
  {
    path: 'business/:id',
    loadComponent: () => import('./features/business-detail/business-detail.page').then((m) => m.BusinessDetailPage),
    data: { title: 'Detalle del negocio' },
  },

  /** Marketplace collections. */
  {
    path: 'tours',
    loadComponent: () => import('./features/tours/tours.page').then((m) => m.ToursPage),
    data: { title: 'Tours y experiencias', preload: true },
  },
  {
    path: 'restaurants',
    loadComponent: () => import('./features/restaurants/restaurants.page').then((m) => m.RestaurantsPage),
    data: { title: 'Restaurantes', preload: true },
  },
  {
    path: 'inns',
    loadComponent: () => import('./features/inns/inns.page').then((m) => m.InnsPage),
    data: { title: 'Posadas nativas', preload: true },
  },
  {
    path: 'promotions',
    loadComponent: () => import('./features/promotions/promotions.page').then((m) => m.PromotionsPage),
    data: { title: 'Promociones', preload: true },
  },

  /** Marketplace detail pages pushed above the tab stack. */
  {
    path: 'tour/:slug',
    loadComponent: () => import('./features/tour-detail/tour-detail.page').then((m) => m.TourDetailPage),
    data: { title: 'Detalle del tour' },
  },
  {
    path: 'restaurant/:slug',
    loadComponent: () => import('./features/restaurant-detail/restaurant-detail.page').then((m) => m.RestaurantDetailPage),
    data: { title: 'Detalle del restaurante' },
  },
  {
    path: 'inn/:slug',
    loadComponent: () => import('./features/inn-detail/inn-detail.page').then((m) => m.InnDetailPage),
    data: { title: 'Detalle de la posada' },
  },

  /** Flujo de reserva anónima (sin login). */
  {
    path: 'booking/new',
    loadComponent: () => import('./features/booking-form/booking-form.page').then((m) => m.BookingFormPage),
    data: { title: 'Reservar' },
  },

  /** Páginas informativas y de ajustes (accesibles desde el hub de Contacto). */
  {
    path: 'noticias',
    loadComponent: () => import('./features/news/news.page').then((m) => m.NewsPage),
    data: { title: 'Noticias' },
  },
  {
    path: 'noticias/:slug',
    loadComponent: () => import('./features/news-detail/news-detail.page').then((m) => m.NewsDetailPage),
    data: { title: 'Noticia' },
  },
  {
    path: 'configuracion',
    loadComponent: () => import('./features/settings/settings.page').then((m) => m.SettingsPage),
    data: { title: 'Configuración' },
  },
  {
    path: 'acerca-de',
    loadComponent: () => import('./features/about/about.page').then((m) => m.AboutPage),
    data: { title: 'Acerca de' },
  },
  {
    path: 'privacidad',
    loadComponent: () => import('./features/privacy/privacy.page').then((m) => m.PrivacyPage),
    data: { title: 'Política de Privacidad' },
  },

  { path: '**', redirectTo: '/home' },
];