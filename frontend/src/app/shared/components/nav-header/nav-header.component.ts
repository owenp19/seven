import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { IonIcon, MenuController } from '@ionic/angular';

import { HapticsService } from '../../../core/services/haptics.service';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';

interface HeaderLink {
  label: string;
  route: string;
}

/**
 * NavHeader
 *
 * Shared top navigation bar (the one used on Home): brand logo on the
 * left and the assistant + search shortcuts on the right. On desktop an
 * inline horizontal navigation appears next to the logo.
 * Used by every tabbed page so the whole app shares the same header.
 */
@Component({
  selector: 'app-nav-header',
  templateUrl: './nav-header.component.html',
  styleUrls: ['./nav-header.component.scss'],
  standalone: true,
  imports: [IonIcon, RouterLink, RouterLinkActive, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavHeaderComponent {
  private readonly router = inject(Router);
  private readonly haptics = inject(HapticsService);
  private readonly menuController = inject(MenuController);

  readonly links: HeaderLink[] = [
    { label: 'Inicio', route: '/home' },
    { label: 'Explorar', route: '/explore' },
    { label: 'Reservas', route: '/bookings' },
  ];

  /** Abre el cajón lateral compartido (ver `SideMenuComponent`). */
  openMenu(): void {
    void this.haptics.selection();
    void this.menuController.open('seven-menu');
  }

  openSearch(): void {
    void this.haptics.selection();
    void this.router.navigate(['/explore']);
  }
}