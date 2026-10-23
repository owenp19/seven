import { Component, OnInit, DestroyRef, inject } from '@angular/core';
import { Router, NavigationStart, NavigationEnd, NavigationCancel, NavigationError } from '@angular/router';
import { IonApp, IonRouterOutlet } from '@ionic/angular';
import { Capacitor } from '@capacitor/core';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';
import { filter } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { registerIcons } from './shared/icons/register-icons';
import { DataCacheService } from './core/services/data-cache.service';
import { HomeDataSeeder } from './core/services/home-data-seeder.service';
import { LoaderGateService } from './core/services/loader-gate.service';
import { NativeService } from './core/services/native.service';
import { TextSizeService } from './core/services/text-size.service';
import { ThemeService } from './core/services/theme.service';
import { GlobalLoaderComponent } from './shared/components/global-loader/global-loader.component';
import { SideMenuComponent } from './shared/components/menu/side-menu/side-menu.component';

/**
 * AppComponent
 *
 * Raíz de la aplicación: un único `ion-router-outlet` (la navegación principal
 * vive en la barra inferior compartida, ya no en un cajón lateral) y el loader
 * global que cubre cada transición de ruta.
 */
@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: true,
  imports: [IonApp, IonRouterOutlet, GlobalLoaderComponent, SideMenuComponent],
})
export class AppComponent implements OnInit {
  private readonly router = inject(Router);
  private readonly homeData = inject(HomeDataSeeder);
  private readonly loaderGate = inject(LoaderGateService);
  private readonly destroy = inject(DestroyRef);
  private readonly native = inject(NativeService);

  // Estos dos se inyectan solo por su efecto secundario en el constructor:
  // escriben `data-seven-theme` y el `font-size` raíz en `<html>`. Si nadie
  // los instancia hasta que el usuario abre Configuración, el tema y el
  // tamaño de texto guardados no se aplican hasta entonces.
  private readonly _theme = inject(ThemeService);
  private readonly _textSize = inject(TextSizeService);
  private readonly dataCache = inject(DataCacheService);

  /** Global loader: initially visible at boot and shown during every navigation. */
  showLoader = true;

  /** Minimum time the loader stays visible after it appears. */
  private readonly loaderMinVisible = 600;

  /** Timeout handle used to schedule the loader hide. */
  private hideLoaderTimer: ReturnType<typeof setTimeout> | undefined;

  /** Timestamp of the last time the loader was shown. */
  private loaderShownAt = 0;

  /** El splash nativo solo se retira una vez por sesión. */
  private splashHidden = false;

  constructor() {
    registerIcons();
  }

  ngOnInit(): void {
    void this.applyNativeChrome();

    // Antes que nada: si iOS purgó el `localStorage`, el identificador de
    // instalación se regenera y el usuario gana un segundo comentario del
    // mismo teléfono. Se reconcilia con la copia durable antes de que salga
    // la primera petición, que es lo que adjunta la cabecera `X-Install-Id`.
    void this.native.restoreInstallId();

    // Los tokens de acceso de las reservas también viven en Preferences (la
    // copia duradera), igual que el install id: se reconcilian con
    // `localStorage` antes de que el primer polling de "Mis reservas" intente
    // consultarlas.
    void this.native.restoreBookingTokens();

    this.armSplashWatchdog();

    // Restaura el catálogo descargado para offline ANTES del prefetch: si el
    // viajero no tiene cobertura, el prefetch falla por red y sin este paso
    // la home saldría vacía aunque tenga el catálogo en el móvil.
    void this.restoreOfflineCatalog();

    // Precalienta banners/categorías/destacados en cache para que la Home
    // salga cargada desde el primer ingreso (sin refrescar la página).
    this.homeData.prefetch();

    this.watchRouteTransitions();
  }

  /**
   * Keeps a global loader over the screen during every navigation so the
   * user never sees the current page while the next one is loading:
   * shown as soon as a navigation starts and hidden once it finishes
   * (after a small delay so the incoming page paints underneath).
   *
   * A page can opt out with `LoaderGateService.skipNext()` when it already
   * renders a full-screen experience worth watching while the next chunk
   * loads (the Welcome background, for instance).
   */
  private watchRouteTransitions(): void {
    this.router.events
      .pipe(takeUntilDestroyed(this.destroy))
      .subscribe((event) => {
        if (event instanceof NavigationStart) {
          if (this.loaderGate.consume()) {
            this.clearHideLoaderTimer();
            // The page opting out owns the transition, so the global loader
            // must be down even if the user tapped before the boot timer
            // finished hiding it; otherwise it would resurface as a flat
            // overlay on top of the page it is supposed to reveal.
            this.hideLoader();
            return;
          }

          this.showLoader = true;
          this.loaderShownAt = Date.now();
          this.clearHideLoaderTimer();
        } else if (event instanceof NavigationEnd || event instanceof NavigationCancel || event instanceof NavigationError) {
          this.scheduleHideLoader();
        }
      });
  }

  private scheduleHideLoader(): void {
    this.clearHideLoaderTimer();
    const remaining = this.loaderMinVisible - (Date.now() - this.loaderShownAt);
    this.hideLoaderTimer = setTimeout(() => this.hideLoader(), Math.max(260, remaining));
  }

  /**
   * Baja el loader global y, si es el primer pantallazo de la sesión, el splash
   * nativo.
   *
   * El punto clave: el splash se retira cuando la web **ya ha pintado**, no
   * cuando haya pasado un tiempo. Con `launchAutoHide` el plugin lo escondía a
   * los 1200 ms haya arrancado Angular o no, y en un móvil lento eso destapaba
   * un webview blanco. Como ambos usan el mismo azul de marca, el relevo es
   * invisible.
   */
  private hideLoader(): void {
    this.showLoader = false;
    this.dismissSplash();
  }

  /** Retira el splash nativo una sola vez. */
  private dismissSplash(): void {
    if (this.splashHidden || !Capacitor.isNativePlatform()) {
      return;
    }

    this.splashHidden = true;

    void SplashScreen.hide({ fadeOutDuration: 200 }).catch(() => {
      // Non-fatal: en web no hay splash y en nativo el plugin puede no estar.
    });
  }

  /**
   * Red de seguridad del splash.
   *
   * Si por lo que sea no llegara un `NavigationEnd` —una redirección rota, un
   * chunk que no descarga— el usuario se quedaría mirando el splash
   * indefinidamente sin poder hacer nada. Un tope de tiempo es preferible a
   * una pantalla muerta, aunque menos de lo que sería no hacer nada.
   */
  private armSplashWatchdog(): void {
    if (!Capacitor.isNativePlatform()) {
      return;
    }

    setTimeout(() => this.dismissSplash(), 5000);
  }

  private clearHideLoaderTimer(): void {
    if (this.hideLoaderTimer !== undefined) {
      clearTimeout(this.hideLoaderTimer);
      this.hideLoaderTimer = undefined;
    }
  }

  private async applyNativeChrome(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      return;
    }
    try {
      await StatusBar.setOverlaysWebView({ overlay: true });
      await StatusBar.setStyle({ style: Style.Light });
      await StatusBar.setBackgroundColor({ color: '#0B2D4A' });
    } catch {
      // Non-fatal
    }
  }

  /**
   * Vuelve a cargar en memoria el catálogo que se descargó para offline.
   *
   * La copia vive en `Storage`, no en la caché de sesión, así que sin esto
   * el modo sin conexión solo servía de lo que se hubiera usado en esta
   * ejecución: cerrar la app y volver a abrirla en el ferry vaciaba la
   * pantalla. Un fallo aquí no debe tumbar el arranque, de ahí el `catch`.
   */
  private async restoreOfflineCatalog(): Promise<void> {
    try {
      await this.dataCache.restore();
    } catch {
      // Non-fatal: seguimos con la caché de sesión.
    }
  }
}
