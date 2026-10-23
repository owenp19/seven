import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { IonContent, IonMenu, MenuController } from '@ionic/angular';
import { filter } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { MenuHeaderComponent } from '../menu-header/menu-header.component';
import { MenuItemComponent } from '../menu-item/menu-item.component';
import { TranslatePipe } from '../../../pipes/translate/translate.pipe';
import type { TranslationKey } from '../../../../core/services/translation.service';

interface SideMenuLink {
  icon: string;
  labelKey: TranslationKey;
  route: string;
  /** Route prefixes que mantienen el ítem resaltado. */
  matches: string[];
}

/**
 * SideMenu
 *
 * Cajón lateral (slider) que se abre desde el icono de hamburguesa del header
 * compartido. Reúne las secciones que no caben en la barra inferior:
 * Noticias, Configuración, Acerca de y Privacidad, además de los atajos
 * principales para lecturas largas.
 *
 * Va montado una sola vez en `AppComponent`, como hermano del router-outlet,
 * que es el nodo que Ionic necesita como `contentId` para deslizar el panel
 * por encima sin recortar la página.
 */
@Component({
  selector: 'app-side-menu',
  templateUrl: './side-menu.component.html',
  styleUrls: ['./side-menu.component.scss'],
  standalone: true,
  imports: [IonMenu, IonContent, MenuHeaderComponent, MenuItemComponent, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SideMenuComponent {
  private readonly router = inject(Router);
  private readonly menuController = inject(MenuController);
  private readonly destroy = inject(DestroyRef);

  /** Id público del cajón; el header de navegación lo abre por este nombre. */
  readonly menuId = 'seven-menu';

  readonly links: SideMenuLink[] = [
    { icon: 'home', labelKey: 'nav.home', route: '/home', matches: ['/home'] },
    { icon: 'newspaper-outline', labelKey: 'menu.news', route: '/noticias', matches: ['/noticias'] },
    { icon: 'settings-outline', labelKey: 'menu.settings', route: '/configuracion', matches: ['/configuracion'] },
    { icon: 'information-circle-outline', labelKey: 'menu.about', route: '/acerca-de', matches: ['/acerca-de'] },
    { icon: 'shield-checkmark-outline', labelKey: 'menu.privacy', route: '/privacidad', matches: ['/privacidad'] },
  ];

  private readonly activeUrl = signal(this.router.url);

  constructor() {
    this.router.events
      .pipe(
        filter((event): event is NavigationEnd => event instanceof NavigationEnd),
        takeUntilDestroyed(this.destroy),
      )
      .subscribe((event) => this.activeUrl.set(event.urlAfterRedirects ?? event.url));
  }

  isActive(link: SideMenuLink): boolean {
    const current = this.activeUrl();
    return link.matches.some((prefix) => current === prefix || current.startsWith(`${prefix}/`));
  }

  go(link: SideMenuLink): void {
    void this.menuController.close(this.menuId);
    void this.router.navigate([link.route]);
  }
}
