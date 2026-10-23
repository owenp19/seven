import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { IonContent, IonIcon, IonRefresher, IonRefresherContent } from '@ionic/angular';

import { Observable, Subject, catchError, of, startWith, switchMap, tap } from 'rxjs';

import { Inn } from '../../models';
import { InnService } from '../../services/inn.service';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { SevenResultCardComponent } from '../../shared/components/seven-result-card/seven-result-card.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { HapticsService } from '../../core/services/haptics.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { inputValue, matchesQuery } from '../../shared/utils/search';

/**
 * InnsPage
 *
 * Catálogo de posadas nativas (100% API). Página empujada sobre el stack
 * de tabs; cada tarjeta navega al detalle por slug. El buscador filtra en
 * cliente sobre el feed cargado.
 */
@Component({
  selector: 'app-inns',
  templateUrl: './inns.page.html',
  styleUrls: ['./inns.page.scss'],
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
export class InnsPage implements OnInit {
  private readonly inns = inject(InnService);
  private readonly haptics = inject(HapticsService);
  private readonly dataCache = inject(DataCacheService);

  private readonly reload$ = new Subject<void>();
  private readonly data = signal<Inn[]>([]);

  readonly query = signal('');

  readonly filtered = computed(() =>
    this.data().filter((inn) =>
      matchesQuery(this.query(), inn.name, inn.description, inn.location),
    ),
  );

  inns$!: Observable<Inn[]>;

  ngOnInit(): void {
    this.inns$ = this.reload$.pipe(
      startWith(null),
      switchMap(() => this.inns.getAll().pipe(catchError(() => of([] as Inn[])))),
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
    this.dataCache.invalidate('inns');
    this.reload$.next();
    const target = event.target as HTMLIonRefresherElement;
    Promise.resolve().then(() => setTimeout(() => target.complete(), 400));
  }
}
