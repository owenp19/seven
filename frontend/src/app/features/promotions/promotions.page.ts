import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AsyncPipe, DatePipe } from '@angular/common';
import { IonContent, IonIcon, IonRefresher, IonRefresherContent } from '@ionic/angular';

import { Observable, Subject, catchError, of, startWith, switchMap, tap } from 'rxjs';

import { Promotion } from '../../models';
import { PromotionService } from '../../services/promotion.service';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { HapticsService } from '../../core/services/haptics.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { inputValue, matchesQuery } from '../../shared/utils/search';

/**
 * PromotionsPage
 *
 * Ofertas de temporada de los proveedores (100% API). El buscador filtra en
 * cliente sobre el feed cargado.
 */
@Component({
  selector: 'app-promotions',
  templateUrl: './promotions.page.html',
  styleUrls: ['./promotions.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    DatePipe,
    IonContent,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    PageHeaderComponent,
    SevenContextLoaderComponent,
    SafeImgDirective,
    CurrencyPipe,
    TranslatePipe,
  ],
})
export class PromotionsPage implements OnInit {
  private readonly promotions = inject(PromotionService);
  private readonly haptics = inject(HapticsService);
  private readonly dataCache = inject(DataCacheService);

  private readonly reload$ = new Subject<void>();
  private readonly data = signal<Promotion[]>([]);

  readonly query = signal('');

  readonly filtered = computed(() =>
    this.data().filter((promotion) =>
      matchesQuery(this.query(), promotion.title, promotion.providerName, promotion.description),
    ),
  );

  promotions$!: Observable<Promotion[]>;

  ngOnInit(): void {
    this.promotions$ = this.reload$.pipe(
      startWith(null),
      switchMap(() => this.promotions.getAll().pipe(catchError(() => of([] as Promotion[])))),
      tap((list) => this.data.set(list)),
    );
  }

  onSearch(event: Event): void {
    this.query.set(inputValue(event));
  }

  clearSearch(): void {
    this.query.set('');
  }

  onRefresh(event: Event): void {
    void this.haptics.selection();
    this.dataCache.invalidate('promotions');
    this.reload$.next();
    const target = event.target as HTMLIonRefresherElement;
    Promise.resolve().then(() => setTimeout(() => target.complete(), 400));
  }
}
