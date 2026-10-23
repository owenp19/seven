import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  CUSTOM_ELEMENTS_SCHEMA,
  ElementRef,
  Input,
  OnChanges,
  OnDestroy,
  SimpleChanges,
  ViewChild,
  signal,
} from '@angular/core';
import { register } from 'swiper/element/bundle';

import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { mediaSources } from '../../utils/media';

register();

/**
 * MediaCarousel
 *
 * Carrusel de imágenes con avance automático para las portadas de detalle
 * (tour, restaurante, posada, lugar). Es un `swiper-container` real, igual
 * que el carrusel de noticias, pero genérico: recibe cualquier lista de URLs
 * y dibuja sus propios puntos en lugar de depender de un selector CSS.
 *
 * Decisiones que lo hacen reutilizable:
 *
 * - El autoplay se detiene solo si hay una sola imagen, y respeta la
 *   preferencia global de "reducir animaciones" (`html[data-reduce-motion]`)
 *   mediante un `MutationObserver`, para frenar en caliente.
 * - Si `images` cambia (las páginas de detalle se reutilizan en el
 *   `IonRouterOutlet`, así que el componente recibe la lista nueva sin
 *   recrearse) se reconstruye el Swiper en vez de dejar slides viejos.
 */
@Component({
  selector: 'app-media-carousel',
  templateUrl: './media-carousel.component.html',
  styleUrls: ['./media-carousel.component.scss'],
  standalone: true,
  imports: [SafeImgDirective, TranslatePipe],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MediaCarouselComponent implements AfterViewInit, OnChanges, OnDestroy {
  /** URLs a mostrar. Se filtran vacíos y duplicados en `slides`. */
  @Input({ required: true }) images: readonly string[] = [];

  /** Texto alternativo compartido por todas las imágenes. */
  @Input() alt = '';

  /** Milisegundos entre slide y slide. */
  @Input() autoplayDelay = 5000;

  /**
   * Dibuja los puntos de paginación.
   *
   * Los heroes de detalle los apagan con `[showDots]="false"`: allí la imagen
   * ocupa todo el alto y los puntos se leen como ruido sobre la foto, ya que
   * el propio gesto de deslizar y el counter de fotos comunican la posición.
   */
  @Input() showDots = true;

  @ViewChild('swiperEl', { static: true }) swiperRef!: ElementRef<HTMLElement>;

  /** Índice visible, para los puntos. */
  readonly activeIndex = signal(0);

  private swiper: any = null;
  private initialized = false;
  private destroyed = false;
  private motionObserver?: MutationObserver;

  /** Lista final de slides: sin URLs vacías ni repetidas, en su orden. */
  get slides(): string[] {
    return mediaSources(...this.images);
  }

  get hasMultiple(): boolean {
    return this.slides.length > 1;
  }

  ngAfterViewInit(): void {
    this.initSwiper();
    this.watchMotionPreference();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['images'] && this.initialized) {
      this.rebuild();
    }
  }

  ngOnDestroy(): void {
    this.destroyed = true;
    this.motionObserver?.disconnect();
    this.destroySwiper();
  }

  goTo(index: number): void {
    this.swiper?.slideTo(index);
  }

  private initSwiper(): void {
    const el = this.swiperRef?.nativeElement;

    if (!el || this.destroyed) {
      return;
    }

    const multiple = this.hasMultiple;

    Object.assign(el, {
      autoplay: multiple
        ? { delay: this.autoplayDelay, disableOnInteraction: false, pauseOnMouseEnter: true }
        : false,
      loop: multiple,
      speed: 480,
      grabCursor: multiple,
      observer: true,
      observeParents: true,
      watchOverflow: true,
    });

    (el as any).initialize();

    this.swiper = (el as any).swiper ?? null;
    this.initialized = true;
    this.activeIndex.set(0);

    if (this.swiper) {
      // `destroySwiper` deja `this.swiper` en null antes de destruir el
      // Swiper, y el `destroy` dispara un `slideChange` de cierre: sin el
      // encadenamiento opcional, ese último evento leía `activeIndex` de
      // null y reventaba la consola al desmontar la portada.
      this.swiper.on('slideChange', () => this.activeIndex.set(this.swiper?.activeIndex ?? 0));
    }

    this.applyMotionPreference();
  }

  /** Las páginas de detalle reutilizan la instancia: hay que rehacer el Swiper. */
  private rebuild(): void {
    this.destroySwiper();
    this.activeIndex.set(0);

    // Espera a que Angular pinte la lista nueva de slides antes de medir.
    setTimeout(() => {
      if (!this.destroyed) {
        this.initSwiper();
      }
    });
  }

  private destroySwiper(): void {
    const el = this.swiperRef?.nativeElement as any;

    this.swiper = null;
    this.initialized = false;

    if (el?.swiper) {
      el.swiper.destroy(true, true);
      delete el.swiper;
    }
  }

  /**
   * "Reducir animaciones" es una preferencia global de Configuración, así que
   * el carrusel la vigila en `html` y frena/arranca sin que la página que lo
   * hospeda tenga que enterarse.
   */
  private watchMotionPreference(): void {
    if (typeof MutationObserver === 'undefined') {
      return;
    }

    this.motionObserver = new MutationObserver(() => this.applyMotionPreference());
    this.motionObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-reduce-motion'],
    });
  }

  private applyMotionPreference(): void {
    if (!this.swiper || !this.hasMultiple) {
      return;
    }

    const reduced = document.documentElement.getAttribute('data-reduce-motion') === 'true';

    if (reduced) {
      this.swiper.autoplay?.stop();
    } else {
      this.swiper.autoplay?.start();
    }
  }
}
