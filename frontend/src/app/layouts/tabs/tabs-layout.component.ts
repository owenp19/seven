import { Component, ChangeDetectionStrategy, DestroyRef, inject, signal } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { NavigationEnd, Router } from '@angular/router';
import { IonIcon, IonRouterOutlet } from '@ionic/angular';

import { Observable, filter } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { NavHeaderComponent } from '../../shared/components/nav-header/nav-header.component';
import { BottomNavComponent } from '../../shared/components/bottom-nav/bottom-nav.component';
import { NativeService } from '../../core/services/native.service';

/**
 * TabsLayout
 *
 * Navigation shell for the main browsing areas: a shared top header
 * (logo + search), an offline banner and the shared bottom menu, exactly as
 * rendered on Home, around the routed pages.
 *
 * The Seven AI assistant ships its own identity header, so the shared one is
 * hidden there while the bottom navigation stays visible (with the assistant
 * highlighted in the centre).
 */
@Component({
  selector: 'app-tabs-layout',
  templateUrl: './tabs-layout.component.html',
  styleUrls: ['./tabs-layout.component.scss'],
  standalone: true,
  imports: [AsyncPipe, IonRouterOutlet, IonIcon, NavHeaderComponent, BottomNavComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TabsLayoutComponent {
  private readonly native = inject(NativeService);
  private readonly router = inject(Router);
  private readonly destroy = inject(DestroyRef);

  readonly online$: Observable<boolean> = this.native.networkStatus$;

  /** La cabecera compartida se oculta en el asistente (tiene la suya). */
  readonly showHeader = signal(!this.router.url.startsWith('/assistant'));

  constructor() {
    this.router.events
      .pipe(
        filter((event): event is NavigationEnd => event instanceof NavigationEnd),
        takeUntilDestroyed(this.destroy),
      )
      .subscribe((event) => this.showHeader.set(!(event.urlAfterRedirects ?? '').startsWith('/assistant')));
  }
}
