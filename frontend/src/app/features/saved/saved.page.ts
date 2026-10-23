import { AsyncPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IonContent, IonIcon, IonSkeletonText } from '@ionic/angular';
import { Observable, catchError, forkJoin, map, of, startWith, switchMap } from 'rxjs';

import type { AsyncState } from '../../interfaces';
import { FavoriteRef, FavoritesService } from '../../core/services/favorites.service';
import { HapticsService } from '../../core/services/haptics.service';
import { TourService } from '../../services/tour.service';
import { RestaurantService } from '../../services/restaurant.service';
import { InnService } from '../../services/inn.service';
import { BusinessService } from '../../services/business.service';
import { SectionHeaderComponent } from '../../shared/components/section-header/section-header.component';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

/** A favorite resolved into everything a card needs to render and link. */
interface SavedItem {
  key: string;
  ref: FavoriteRef;
  name: string;
  image: string;
  category: string;
  route: (string | number)[];
}

/**
 * SavedPage
 *
 * Lists the services the traveller bookmarked. Favorites only store a
 * `{ type, id, slug }` reference, so each entry is fetched individually
 * (by slug, or by id for businesses) instead of downloading the whole
 * catalog — a lightweight request per saved item, and no request at all
 * when the list is empty. Items that no longer exist are dropped.
 */
@Component({
  selector: 'app-saved',
  templateUrl: './saved.page.html',
  styleUrls: ['./saved.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    IonSkeletonText,
    RouterLink,
    SafeImgDirective,
    TranslatePipe,
    SectionHeaderComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SavedPage {
  private readonly favorites = inject(FavoritesService);
  private readonly haptics = inject(HapticsService);
  private readonly tours = inject(TourService);
  private readonly restaurants = inject(RestaurantService);
  private readonly inns = inject(InnService);
  private readonly businesses = inject(BusinessService);

  readonly ghosts = [1, 2, 3];

  readonly items$: Observable<AsyncState<SavedItem[]>> = this.favorites.favorites$.pipe(
    switchMap((refs) => {
      if (refs.length === 0) {
        return of<AsyncState<SavedItem[]>>({ status: 'ready', data: [] });
      }

      return forkJoin(refs.map((ref) => this.resolve$(ref).pipe(catchError(() => of(null))))).pipe(
        map((items) => ({
          status: 'ready' as const,
          data: items.filter((item): item is SavedItem => item !== null),
        })),
      );
    }),
    startWith<AsyncState<SavedItem[]>>({ status: 'loading' }),
  );

  /** Fetches the single referenced entity and maps it to a card (null if gone). */
  private resolve$(ref: FavoriteRef): Observable<SavedItem | null> {
    const key = `${ref.type}:${ref.id}`;

    if (ref.type === 'tour') {
      return this.tours.getBySlug(ref.slug).pipe(
        map((tour) =>
          tour
            ? {
                key,
                ref,
                name: tour.name,
                image: tour.cover,
                category: tour.category ?? 'Tour',
                route: ['/tour', tour.slug],
              }
            : null,
        ),
      );
    }

    if (ref.type === 'restaurant') {
      return this.restaurants.getBySlug(ref.slug).pipe(
        map((restaurant) =>
          restaurant
            ? {
                key,
                ref,
                name: restaurant.name,
                image: restaurant.cover,
                category: restaurant.category ?? 'Restaurante',
                route: ['/restaurant', restaurant.slug],
              }
            : null,
        ),
      );
    }

    if (ref.type === 'inn') {
      return this.inns.getBySlug(ref.slug).pipe(
        map((inn) =>
          inn
            ? {
                key,
                ref,
                name: inn.name,
                image: inn.coverImage,
                category: 'Posada nativa',
                route: ['/inn', inn.slug],
              }
            : null,
        ),
      );
    }

    return this.businesses.getById(ref.id).pipe(
      map((business) => ({
        key,
        ref,
        name: business.name,
        image: business.coverImage ?? '',
        category: 'Negocio',
        route: ['/business', business.id],
      })),
    );
  }

  /** Removes a service from the saved list. */
  remove(event: Event, item: SavedItem): void {
    event.stopPropagation();
    event.preventDefault();
    void this.haptics.selection();
    void this.favorites.toggle(item.ref.type, item.ref.id, item.ref.slug);
  }
}
