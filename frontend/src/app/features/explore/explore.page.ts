import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { AsyncPipe } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonContent,
  IonIcon,
  IonModal,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
} from '@ionic/angular';
import {
  Observable,
  Subject,
  catchError,
  combineLatest,
  debounceTime,
  distinctUntilChanged,
  map,
  merge,
  of,
  startWith,
  switchMap,
} from 'rxjs';

import { Business, Category } from '../../models';
import { describeApiError } from '../../interfaces';
import type { AsyncState } from '../../interfaces';
import { BusinessService } from '../../services/business.service';
import { CategoryService } from '../../services/category.service';
import { SevenResultCardComponent } from '../../shared/components/seven-result-card/seven-result-card.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

type SortMode = 'relevance' | 'rating' | 'distance';

interface Coords {
  lat: number;
  lng: number;
}

/** Great-circle distance between two coordinates, in kilometres. */
function haversineKm(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const toRad = (deg: number): number => (deg * Math.PI) / 180;
  const earthRadiusKm = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
  return 2 * earthRadiusKm * Math.asin(Math.sqrt(a));
}

/**
 * ExplorePage
 *
 * The discovery directory of SEVEN: a search field, category chips and
 * secondary filters that narrow a list of businesses into compact result
 * cards. Reads use the public `/businesses` endpoint, which is the only
 * unified source that supports free-text (`q`) and category filtering.
 */
@Component({
  selector: 'app-explore',
  templateUrl: './explore.page.html',
  styleUrls: ['./explore.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    ReactiveFormsModule,
    IonContent,
    IonIcon,
    IonModal,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
    SevenContextLoaderComponent,
    SevenResultCardComponent,
    TranslatePipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ExplorePage {
  private readonly businesses = inject(BusinessService);
  private readonly categoryService = inject(CategoryService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly searchControl = new FormControl('');
  readonly searchText = signal('');
  readonly filtersOpen = signal(false);
  readonly locationError = signal(false);
  readonly activeCategory = signal('');

  readonly sort = signal<SortMode>('relevance');
  readonly minRating = signal(0);
  readonly nearby = signal<Coords | null>(null);

  readonly hasAdvancedFilters = computed(() => this.minRating() > 0);
  readonly ratingOptions = [0, 3, 4, 4.5];

  private readonly reload$ = new Subject<void>();

  readonly categories$: Observable<AsyncState<Category[]>>;
  readonly businesses$: Observable<AsyncState<Business[]>>;

  constructor() {
    this.searchControl.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((value) => this.searchText.set(value ?? ''));

    this.route.queryParamMap
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((params) => this.activeCategory.set(params.get('category') ?? ''));

    this.categories$ = this.reload$.pipe(
      startWith(void 0),
      switchMap(() =>
        this.categoryService.getAll().pipe(
          map((data): AsyncState<Category[]> => ({
            status: 'ready',
            data: data.filter((category) => category.businessCount > 0),
          })),
          startWith<AsyncState<Category[]>>({ status: 'loading' }),
          catchError((error) =>
            of<AsyncState<Category[]>>({ status: 'error', message: describeApiError(error) }),
          ),
        ),
      ),
    );

    const query$ = this.searchControl.valueChanges.pipe(
      startWith(''),
      debounceTime(300),
      distinctUntilChanged(),
      map((value) => (value ?? '').trim()),
    );
    const refreshQuery$ = this.reload$.pipe(map(() => (this.searchControl.value ?? '').trim()));

    const data$ = combineLatest([merge(query$, refreshQuery$), toObservable(this.activeCategory)]).pipe(
      switchMap(([query, category]) =>
        this.businesses.list({ q: query, category }).pipe(
          map((page) => page.items),
          map((items): AsyncState<Business[]> => ({ status: 'ready', data: items })),
          startWith<AsyncState<Business[]>>({ status: 'loading' }),
          catchError((error) =>
            of<AsyncState<Business[]>>({ status: 'error', message: describeApiError(error) }),
          ),
        ),
      ),
    );

    this.businesses$ = combineLatest([
      data$,
      toObservable(this.sort),
      toObservable(this.minRating),
      toObservable(this.nearby),
    ]).pipe(
      map(([state, sort, minRating, nearby]) => {
        if (state.status !== 'ready') {
          return state;
        }
        return {
          status: 'ready' as const,
          data: this.decorate(state.data, sort, minRating, nearby),
        };
      }),
    );
  }

  clearSearch(): void {
    this.searchControl.setValue('');
  }

  onRefresh(event: CustomEvent): void {
    this.reload$.next();
    (event.target as HTMLIonRefresherElement)?.complete();
  }

  retry(): void {
    this.reload$.next();
  }

  selectCategory(slug: string): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { category: slug || null },
      queryParamsHandling: 'merge',
      replaceUrl: true,
    });
  }

  toggleBestRated(): void {
    if (this.sort() === 'rating') {
      this.sort.set('relevance');
      return;
    }
    this.nearby.set(null);
    this.sort.set('rating');
  }

  toggleNearby(): void {
    if (this.nearby()) {
      this.nearby.set(null);
      if (this.sort() === 'distance') {
        this.sort.set('relevance');
      }
      return;
    }
    this.requestNearby();
  }

  openFilters(): void {
    this.filtersOpen.set(true);
  }

  closeFilters(): void {
    this.filtersOpen.set(false);
  }

  setSort(mode: SortMode): void {
    if (mode === 'distance') {
      this.requestNearby();
      return;
    }
    this.nearby.set(null);
    this.sort.set(mode);
  }

  setMinRating(value: number): void {
    this.minRating.set(this.minRating() === value ? 0 : value);
  }

  clearFilters(): void {
    this.sort.set('relevance');
    this.minRating.set(0);
    this.nearby.set(null);
    this.searchControl.setValue('');
    this.selectCategory('');
  }

  private requestNearby(): void {
    if (
      typeof navigator === 'undefined' ||
      typeof navigator.geolocation?.getCurrentPosition !== 'function'
    ) {
      this.flashLocationError();
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        this.nearby.set({ lat: position.coords.latitude, lng: position.coords.longitude });
        this.sort.set('distance');
      },
      () => this.flashLocationError(),
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
    );
  }

  private flashLocationError(): void {
    this.locationError.set(true);
    if (typeof window !== 'undefined') {
      window.setTimeout(() => this.locationError.set(false), 4000);
    }
  }

  private decorate(
    items: Business[],
    sort: SortMode,
    minRating: number,
    nearby: Coords | null,
  ): Business[] {
    let list = minRating > 0 ? items.filter((item) => item.ratingAvg >= minRating) : items;

    if (sort === 'rating') {
      list = [...list].sort(
        (a, b) => b.ratingAvg - a.ratingAvg || b.ratingCount - a.ratingCount,
      );
    } else if (sort === 'distance' && nearby) {
      list = [...list].sort((a, b) => this.distanceTo(a, nearby) - this.distanceTo(b, nearby));
    }

    return list;
  }

  private distanceTo(business: Business, from: Coords): number {
    const lat = Number.parseFloat(business.location?.latitude ?? '');
    const lng = Number.parseFloat(business.location?.longitude ?? '');
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return Number.POSITIVE_INFINITY;
    }
    return haversineKm(from.lat, from.lng, lat, lng);
  }
}
