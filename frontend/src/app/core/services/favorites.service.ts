import { Injectable, inject } from '@angular/core';

import { Observable, BehaviorSubject } from 'rxjs';

import { STORAGE } from '../tokens';

export type FavoriteType = 'tour' | 'restaurant' | 'inn' | 'business';

export interface FavoriteRef {
  type: FavoriteType;
  id: number;
  slug: string;
}

export interface RecentView {
  type: FavoriteType;
  id: number;
  slug: string;
  name: string;
  at: number;
}

/**
 * FavoritesService
 *
 * Persists user-local data with @ionic/storage:
 *  - `seven:favorites` — servicios guardados (tours, restaurantes, posadas).
 *  - `seven:recent` — últimos servicios vistos (máx. 20).
 *  - `seven:preferences` — preferencias del usuario (idioma, etc.).
 *
 * El diseño está listo para sincronizar estos elementos con la cuenta del
 * usuario en el futuro (los refs usan `type` + `id` + `slug`).
 */
@Injectable({ providedIn: 'root' })
export class FavoritesService {
  private readonly storage = inject(STORAGE);

  private readonly favoritesSubject = new BehaviorSubject<FavoriteRef[]>([]);
  private readonly recentSubject = new BehaviorSubject<RecentView[]>([]);

  private readonly initPromise: Promise<void>;

  constructor() {
    this.initPromise = this.load();
  }

  /** Reactive list of saved services. */
  get favorites$(): Observable<FavoriteRef[]> {
    return this.favoritesSubject.asObservable();
  }

  /** Reactive list of recently viewed services. */
  get recent$(): Observable<RecentView[]> {
    return this.recentSubject.asObservable();
  }

  /** Instantánea de los guardados (para inventarios y borrados). */
  favoritesSnapshot(): FavoriteRef[] {
    return [...this.favoritesSubject.value];
  }

  /** Instantánea del historial de visitas. */
  recentSnapshot(): RecentView[] {
    return [...this.recentSubject.value];
  }

  /** Whether a service is currently saved. */
  isFavorite(type: FavoriteType, id: number): Promise<boolean> {
    return this.initPromise.then(
      () => this.favoritesSubject.value.some((item) => item.type === type && item.id === id),
    );
  }

  /** Toggles the saved state and returns the new state. */
  async toggle(type: FavoriteType, id: number, slug: string): Promise<boolean> {
    await this.initPromise;

    const favorites = [...this.favoritesSubject.value];
    const index = favorites.findIndex((item) => item.type === type && item.id === id);

    if (index >= 0) {
      favorites.splice(index, 1);
    } else {
      favorites.unshift({ type, id, slug });
    }

    await this.storage.set('seven:favorites', favorites);
    this.favoritesSubject.next(favorites);
    return index < 0;
  }

  /** Marks a service as recently viewed (deduplicated, capped). */
  async track(view: { type: FavoriteType; id: number; slug: string; name: string }): Promise<void> {
    await this.initPromise;

    const recent = this.recentSubject.value.filter(
      (item) => item.type !== view.type || item.id !== view.id,
    );
    recent.unshift({ ...view, at: Date.now() });
    const capped = recent.slice(0, 20);

    await this.storage.set('seven:recent', capped);
    this.recentSubject.next(capped);
  }

  /** Reads a user preference. */
  async getPreference<T>(key: string): Promise<T | null> {
    await this.initPromise;
    const stored = (await this.storage.get('seven:preferences')) as Record<string, unknown> | null;
    const preferences = stored ?? {};
    return (preferences[key] as T | undefined) ?? null;
  }

  /** Stores a user preference. */
  async setPreference(key: string, value: unknown): Promise<void> {
    await this.initPromise;
    const preferences = { ...((await this.storage.get('seven:preferences')) as Record<string, unknown> | null) };
    preferences[key] = value;
    await this.storage.set('seven:preferences', preferences);
  }

  /**
   * Borra guardados, historial y preferencias de este dispositivo.
   *
   * Lo invoca el borrado de datos de la Política de Privacidad, así que
   * vacía tanto el almacenamiento como los subjects en memoria: si no, la
   * UI seguiría mostrando los datos borrados hasta el siguiente arranque.
   */
  async eraseLocal(): Promise<void> {
    await this.initPromise;

    await this.storage.remove('seven:favorites');
    await this.storage.remove('seven:recent');
    await this.storage.remove('seven:preferences');

    this.favoritesSubject.next([]);
    this.recentSubject.next([]);
  }

  private async load(): Promise<void> {
    await this.storage.create();

    const favorites = ((await this.storage.get('seven:favorites')) as FavoriteRef[] | null) ?? [];
    const recent = ((await this.storage.get('seven:recent')) as RecentView[] | null) ?? [];

    this.favoritesSubject.next(favorites);
    this.recentSubject.next(recent);
  }
}