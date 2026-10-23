/**
 * SEVEN ICON LIBRARY
 * ----------------------------------------------------------------------------
 * Central registry of Ionicons used across the SEVEN app.
 *
 * Keeping the icon names here guarantees visual consistency, makes searchable
 * intent, and lets us swap the underlying icon set in a single place.
 *
 * Usage:
 *   <ion-icon [name]="IconLibrary.GASTRONOMY.restaurant"></ion-icon>
 */
export const IconLibrary = {
  // Gastronomía
  GASTRONOMY: {
    restaurant: 'restaurant-outline',
    fastFood: 'fast-food-outline',
    cafe: 'cafe-outline',
    wine: 'wine-outline',
    pizza: 'pizza-outline',
    iceCream: 'ice-cream-outline',
  },

  // Alojamientos
  ACCOMMODATION: {
    home: 'home-outline',
    bed: 'bed-outline',
    business: 'business-outline',
    key: 'key-outline',
  },

  // Turismo
  TOURISM: {
    location: 'location-outline',
    map: 'map-outline',
    compass: 'compass-outline',
    navigate: 'navigate-outline',
    globe: 'globe-outline',
    flag: 'flag-outline',
  },

  // Mar / Isla
  MARINE: {
    boat: 'boat-outline',
    water: 'water-outline',
    fish: 'fish-outline',
    umbrella: 'umbrella-outline',
    sunny: 'sunny-outline',
  },

  // Experiencias
  EXPERIENCE: {
    camera: 'camera-outline',
    leaf: 'leaf-outline',
    sunny: 'sunny-outline',
    telescope: 'telescope-outline',
    musicalNotes: 'musical-notes-outline',
    heart: 'heart-outline',
  },

  // Reservas / Estado
  BOOKING: {
    calendar: 'calendar-outline',
    ticket: 'ticket-outline',
    checkmark: 'checkmark-circle-outline',
    time: 'time-outline',
    people: 'people-outline',
    cash: 'cash-outline',
    wallet: 'wallet-outline',
  },

  // Acciones & UI
  ACTION: {
    search: 'search-outline',
    chevronForward: 'chevron-forward-outline',
    chevronBack: 'chevron-back-outline',
    close: 'close-outline',
    star: 'star-outline',
    starFilled: 'star',
    call: 'call-outline',
    chat: 'chatbubble-ellipses-outline',
    shield: 'shield-checkmark-outline',
    arrowForward: 'arrow-forward-outline',
    settings: 'settings-outline',
    heartOutline: 'heart-outline',
    heartFilled: 'heart',
    share: 'share-social-outline',
    filter: 'funnel-outline',
    bookmark: 'bookmark-outline',
    user: 'person-circle-outline',
    megaphone: 'megaphone-outline',
    menu: 'menu-outline',
  },

  // Marcas / Social
  BRAND: {
    whatsapp: 'logo-whatsapp',
    instagram: 'logo-instagram',
    facebook: 'logo-facebook',
  },

  // Noticias / Ofertas
  NEWS: {
    megaphone: 'megaphone-outline',
    event: 'calendar-number-outline',
    bolt: 'flash-outline',
    sparkles: 'sparkles-outline',
    gift: 'gift-outline',
    newspaper: 'newspaper-outline',
    trending: 'trending-up-outline',
    star: 'star-outline',
  },

  // Navegación (tabs)
  TAB: {
    home: 'home-outline',
    homeFilled: 'home',
    explore: 'compass-outline',
    exploreFilled: 'compass',
    bookings: 'calendar-outline',
    bookingsFilled: 'calendar',
  },
} as const;

export type IconKey = keyof typeof IconLibrary;
