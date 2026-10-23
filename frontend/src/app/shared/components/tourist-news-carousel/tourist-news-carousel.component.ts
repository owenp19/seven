import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  CUSTOM_ELEMENTS_SCHEMA,
  ElementRef,
  Input,
  OnDestroy,
  Output,
  EventEmitter,
  ViewChild,
} from '@angular/core';
import { IonIcon } from '@ionic/angular';
import { register } from 'swiper/element/bundle';

import { TouristNews } from '../../../models';

register();

@Component({
  selector: 'app-tourist-news-carousel',
  templateUrl: './tourist-news-carousel.component.html',
  styleUrls: ['./tourist-news-carousel.component.scss'],
  standalone: true,
  imports: [IonIcon],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TouristNewsCarouselComponent implements AfterViewInit, OnDestroy {
  @Input() slides: TouristNews[] = [];

  /** Deslizamiento automático. Activo por defecto en el hero del Home. */
  @Input() autoplay = true;

  /** Milisegundos entre slide y slide mientras corre el autoplay. */
  @Input() autoplayDelay = 4500;

  /** Notifies the parent that a slide CTA was tapped (p. ej. un banner). */
  @Output() action = new EventEmitter<TouristNews>();

  @ViewChild('swiperEl', { static: true }) swiperRef!: ElementRef<HTMLElement>;

  private swiper: any = null;
  private motionObserver?: MutationObserver;

  /** Minimum slides for loop mode with slides-per-view="1.05" and centered slides. */
  private readonly loopSlideThreshold = 5;

  /** Con una sola diapositiva no hay nada que deslizar. */
  get slidesToLoop(): number {
    return this.displaySlides.length;
  }

  /** Slide set used by the template. Duplicates the news when they cannot
   *  fill Swiper's loop buffer, so loop mode keeps working seamlessly. */
  get displaySlides(): TouristNews[] {
    const { slides } = this;
    if (slides.length <= 1 || slides.length >= this.loopSlideThreshold) {
      return slides;
    }
    const repeats = Math.ceil(this.loopSlideThreshold / slides.length);
    return Array.from({ length: repeats }, () => slides).flat();
  }

  ngAfterViewInit(): void {
    const el = this.swiperRef.nativeElement;
    const loopable = this.slidesToLoop > 1;

    Object.assign(el, {
      // El autoplay solo se configura si hay más de una slide: con una sola,
      // Swiper avisaría de que no hay nada que reproducir.
      autoplay: this.autoplay && loopable
        ? {
            delay: this.autoplayDelay,
            // Que el usuario deslice a mano no cancela el ciclo: el carrusel
            // sigue avanzando solo, como un banner.
            disableOnInteraction: false,
          }
        : false,
      loop: loopable,
      speed: 500,
      pagination: {
        el: '.tn-carousel__pagination',
        clickable: true,
      },
    });
    (el as any).initialize();

    this.swiper = (el as any).swiper ?? null;
    this.applyMotionPreference();
    this.watchMotionPreference();
  }

  ngOnDestroy(): void {
    this.motionObserver?.disconnect();
    const el = this.swiperRef?.nativeElement;
    if (el) {
      (el as any).swiper?.autoplay?.stop();
    }
  }

  /**
   * "Reducir animaciones" es una preferencia global de Configuración, así que
   * el carrusel la vigila en `html` y frena/arranca sin que el Home tenga que
   * enterarse. Mismo criterio que `MediaCarouselComponent`.
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
    if (!this.swiper || this.slidesToLoop <= 1) {
      return;
    }

    const reduced = document.documentElement.getAttribute('data-reduce-motion') === 'true';

    if (reduced) {
      this.swiper.autoplay?.stop();
    } else {
      this.swiper.autoplay?.start();
    }
  }

  onAction(slide: TouristNews, event: Event): void {
    event.stopPropagation();
    this.action.emit(slide);
  }
}
