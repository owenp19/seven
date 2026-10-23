import { Injectable, inject } from '@angular/core';

import { EMPTY, Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';

import { BannerService } from '../../services/banner.service';
import { BusinessService } from '../../services/business.service';
import { CategoryService } from '../../services/category.service';
import { InnService } from '../../services/inn.service';
import { PromotionService } from '../../services/promotion.service';
import { RestaurantService } from '../../services/restaurant.service';
import { TourService } from '../../services/tour.service';

/**
 * HomeDataSeeder
 *
 * Precalienta (en segundo plano) la caché de los datos de la Home tan pronto
 * como arranca la app, con caches compartidas con HomePage vía DataCacheService.
 *
 * Así, cuando el viajero entra por primera vez a Home (sin necesidad de
 * recargar la página), banners, categorías y destacados ya están listos y
 * la sección pinta al instante en lugar de esperar TTD de red desde cero.
 */
@Injectable({ providedIn: 'root' })
export class HomeDataSeeder {
  private readonly categories = inject(CategoryService);
  private readonly businesses = inject(BusinessService);
  private readonly tours = inject(TourService);
  private readonly restaurants = inject(RestaurantService);
  private readonly inns = inject(InnService);
  private readonly banners = inject(BannerService);
  private readonly promotions = inject(PromotionService);

  /** Lanza todas las lecturas de la Home en paralelo, sin bloquear el boot. */
  prefetch(): void {
    const safe = <T>(source$: Observable<T>): Observable<T> => source$.pipe(catchError(() => EMPTY));

    safe(this.categories.getAll()).subscribe();
    safe(this.banners.getAll()).subscribe();

    safe(this.businesses.getFeatured()).subscribe();
    safe(this.tours.getFeatured()).subscribe();
    safe(this.restaurants.getFeatured()).subscribe();
    safe(this.inns.getFeatured()).subscribe();

    safe(this.promotions.getAll()).subscribe();
  }
}