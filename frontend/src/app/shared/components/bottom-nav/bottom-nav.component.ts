import { ChangeDetectionStrategy, Component, OnDestroy, OnInit, inject, signal } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { IonIcon } from '@ionic/angular';
import { Subscription, filter } from 'rxjs';

import { HapticsService } from '../../../core/services/haptics.service';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import type { TranslationKey } from '../../../core/services/translation.service';

interface BottomNavItem {
  /** Stable identity for `@for` tracking (routes can repeat or be absent). */
  id: string;
  icon: string;
  labelKey: TranslationKey;
  /** Target route. */
  route: string;
  /** Route prefixes that keep this item highlighted. */
  matches: string[];
  /** Marca el ítem central (SEVEN AI) para darle un tratamiento distinto. */
  center?: boolean;
}

/**
 * BottomNav
 *
 * Barra inferior compartida de cinco secciones: Inicio, Explorar, SEVEN AI
 * (centro), Reservas y Contacto. SEVEN AI ocupa el centro con un contenedor
 * circular discreto —no un FAB gigante— para que la función principal de la
 * app se reconozca sin dominar la barra. Resalta la sección activa según la
 * ruta.
 */
@Component({
  selector: 'app-bottom-nav',
  templateUrl: './bottom-nav.component.html',
  styleUrls: ['./bottom-nav.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class BottomNavComponent implements OnInit, OnDestroy {
  private readonly router = inject(Router);
  private readonly haptics = inject(HapticsService);

  private routerSub?: Subscription;
  readonly activeRoute = signal<string | null>(null);

  readonly items: BottomNavItem[] = [
    { id: 'home', icon: 'home', labelKey: 'nav.home', route: '/home', matches: ['/home'] },
    { id: 'explore', icon: 'compass-outline', labelKey: 'nav.explore', route: '/explore', matches: ['/explore'] },
    {
      id: 'assistant',
      icon: 'sparkles-outline',
      labelKey: 'nav.assistant',
      route: '/assistant',
      matches: ['/assistant'],
      center: true,
    },
    { id: 'bookings', icon: 'calendar-outline', labelKey: 'nav.bookings', route: '/bookings', matches: ['/bookings'] },
    { id: 'contact', icon: 'chatbubbles-outline', labelKey: 'nav.contact', route: '/contact', matches: ['/contact'] },
  ];

  ngOnInit(): void {
    this.activeRoute.set(this.router.url);
    this.routerSub = this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe((event) => this.activeRoute.set(event.urlAfterRedirects ?? event.url));
  }

  ngOnDestroy(): void {
    this.routerSub?.unsubscribe();
  }

  isActive(item: BottomNavItem): boolean {
    const current = this.activeRoute();
    if (!current || !item.route) {
      return false;
    }
    return item.matches.some((prefix) => current === prefix || current.startsWith(`${prefix}/`));
  }

  go(item: BottomNavItem): void {
    void this.haptics.selection();

    if (this.activeRoute()?.startsWith(item.route)) {
      return;
    }

    void this.router.navigate([item.route]);
  }
}
