import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { IonContent, IonIcon, IonRefresher, IonRefresherContent } from '@ionic/angular';

import { Observable, Subject, catchError, of, startWith, switchMap, tap } from 'rxjs';

import { Tour } from '../../models';
import { TourService } from '../../services/tour.service';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { SevenResultCardComponent } from '../../shared/components/seven-result-card/seven-result-card.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { HapticsService } from '../../core/services/haptics.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { inputValue, matchesQuery } from '../../shared/utils/search';

/**
 * ToursPage
 *
 * Catálogo de tours y experiencias (100% API). Página empujada sobre el
 * stack de tabs; cada tarjeta navega al detalle por slug. El buscador filtra
 * en cliente sobre el feed cargado, ya que la API no expone búsqueda.
 */
@Component({
  selector: 'app-tours',
  templateUrl: './tours.page.html',
  styleUrls: ['./tours.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    PageHeaderComponent,
    SevenContextLoaderComponent,
    SevenResultCardComponent,
    TranslatePipe,
  ],
})
export class ToursPage implements OnInit {
  private readonly tours = inject(TourService);
  private readonly haptics = inject(HapticsService);
  private readonly dataCache = inject(DataCacheService);

  private readonly reload$ = new Subject<void>();
  private readonly data = signal<Tour[]>([]);

  readonly query = signal('');

  readonly filtered = computed(() =>
    this.data().filter((tour) =>
      matchesQuery(this.query(), tour.name, tour.category, tour.subtitle, tour.description),
    ),
  );

  tours$!: Observable<Tour[]>;

  ngOnInit(): void {
    this.tours$ = this.reload$.pipe(
      startWith(null),
      switchMap(() => this.tours.getAll().pipe(catchError(() => of<Tour[]>([])))),
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
    this.dataCache.invalidate('tours');
    this.reload$.next();
    const target = event.target as HTMLIonRefresherElement;
    Promise.resolve().then(() => setTimeout(() => target.complete(), 400));
  }
}
