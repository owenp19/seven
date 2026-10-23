import { Component, OnInit, inject } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { ApiError } from '../../interfaces';
import { Booking, BookingStatus } from '../../models';
import type { BookableType, CreateBookingPayload } from '../../models/customer.model';
import { BookingService } from '../../services/booking.service';
import { BookingsStoreService } from '../../core/services/bookings-store.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { TranslationService } from '../../core/services/translation.service';
import { STORAGE } from '../../core/tokens';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { whatsappLink } from '../../utils/formatters';
import { DEFAULT_OPERATOR_PHONE } from '../../shared/constants/island';
import { BOOKING_STATUS_UI } from '../../shared/constants/booking-status';

type BookingKind = CreateBookingPayload['type'];

/**
 * `bookable_type` válidos, derivados de la misma unión que el payload.
 *
 * El valor llega por query param (navegación), así que es texto sin
 * verificar: se filtra contra la lista en vez de castear a ciegas, o un
 * enlace manipulado mandaría un tipo arbitrario y la API lo rechazaría
 * con un 422 después de todo el ida y vuelta.
 */
const BOOKABLE_TYPES: readonly BookableType[] = [
  'business',
  'accommodation',
  'experience',
  'tour',
  'restaurant',
  'inn',
];

const BOOKING_KINDS: readonly BookingKind[] = ['gastronomic', 'accommodation', 'experience'];

/**
 * BookingFormPage
 *
 * Pushed page (outside the tab stack) that collects the anonymous guest
 * data — without login — and creates a booking attaching the device
 * `install_id`, so status changes reach only this phone later.
 * On success it shows the booking code + WhatsApp shortcut.
 */
@Component({
  selector: 'app-booking-form',
  templateUrl: './booking-form.page.html',
  styleUrls: ['./booking-form.page.scss'],
  standalone: true,
  imports: [ReactiveFormsModule, DatePipe, IonContent, IonIcon, CurrencyPipe, TranslatePipe],
})
export class BookingFormPage implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  private readonly bookingService = inject(BookingService);
  private readonly store = inject(BookingsStoreService);
  private readonly haptics = inject(HapticsService);
  private readonly native = inject(NativeService);
  private readonly i18n = inject(TranslationService);
  private readonly storage = inject(STORAGE);

  title = '';
  location = '';
  price = 0;
  bookableType: BookableType = 'tour';
  bookableId = 0;
  type: BookingKind = 'experience';
  duration = '';

  readonly minGuests = 1;
  readonly maxGuests = 20;
  guests = 1;

  submitting = false;
  error = '';
  success: Booking | null = null;
  copied = false;

  form = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.maxLength(120)]),
    phone: new FormControl('+57 ', [Validators.required, Validators.maxLength(30)]),
    startAt: new FormControl('', [Validators.required]),
    special: new FormControl('', [Validators.maxLength(1000)]),
  });

  readonly startAtMin = this.toLocalInput(this.oneHourFromNow());

  ngOnInit(): void {
    const q = this.route.snapshot.queryParamMap;

    this.title = q.get('title') ?? '';
    this.location = q.get('location') ?? '';
    this.duration = q.get('duration') ?? '';
    this.price = Number(q.get('price') ?? 0);
    this.bookableType = this.oneOf(q.get('bookableType'), BOOKABLE_TYPES) ?? 'tour';
    this.bookableId = Number(q.get('bookableId') ?? 0);
    this.type = this.oneOf(q.get('type'), BOOKING_KINDS) ?? 'experience';

    this.form.patchValue({ startAt: this.toLocalInput(this.tomorrowAtHour(9)) });
    void this.restoreGuest();
  }

  get name(): FormControl<string | null> {
    return this.form.controls.name;
  }

  get phone(): FormControl<string | null> {
    return this.form.controls.phone;
  }

  get startAt(): FormControl<string | null> {
    return this.form.controls.startAt;
  }

  get special(): FormControl<string | null> {
    return this.form.controls.special;
  }

  get estimate(): number {
    return this.price * this.guests;
  }

  statusLabel(status: BookingStatus): string {
    return this.i18n.t(BOOKING_STATUS_UI[status].labelKey);
  }

  addPerson(): void {
    if (this.guests < this.maxGuests) {
      this.guests += 1;
    }
  }

  removePerson(): void {
    if (this.guests > this.minGuests) {
      this.guests -= 1;
    }
  }

  /** Crea la solicitud anónima y almacena la reserva + código. */
  submit(): void {
    if (this.submitting) {
      return;
    }

    this.form.markAllAsTouched();
    if (this.form.invalid || !this.startAt.value) {
      this.error = 'Revisa los campos marcados.';
      void this.haptics.warning();
      return;
    }

    this.submitting = true;
    this.error = '';

    const payload: CreateBookingPayload = {
      bookable_type: this.bookableType,
      bookable_id: this.bookableId,
      type: this.type,
      start_at: this.startAt.value,
      guests: this.guests,
      special_requests: this.special.value?.trim() || null,
      currency: 'COP',
      customer: {
        name: this.name.value?.trim() ?? '',
        phone: this.phone.value?.trim() ?? '',
      },
    };

    this.bookingService.create(payload).subscribe({
      next: async (booking) => {
        this.submitting = false;
        this.success = booking;
        await this.store.add(booking, this.title, this.bookableType, this.bookableId);
        await this.storage.set('seven:guest', {
          name: payload.customer.name,
          phone: payload.customer.phone,
        });
        void this.haptics.success();
        void this.notifyCreated();
      },
      error: (err: ApiError) => {
        this.submitting = false;
        this.error =
          err?.fields?.['start_at']?.[0] ??
          err?.message ??
          'No pudimos completar la reserva. Intenta de nuevo.';
        void this.haptics.warning();
      },
    });
  }

  /** Copia el código de reserva al portapapeles. */
  async copyCode(code: string): Promise<void> {
    await navigator.clipboard?.writeText(code);
    this.copied = true;
    setTimeout(() => (this.copied = false), 2500);
  }

  /** WhatsApp al operador con el código (sin login). */
  operatorWhatsapp(): string {
    return whatsappLink(
      DEFAULT_OPERATOR_PHONE,
      `Hola, acabo de reservar "${this.title}" en SEVEN con el código ${this.success?.bookingCode}. ¿Pueden confirmar la disponibilidad?`,
    );
  }

  goToBookings(): void {
    void this.router.navigate(['/bookings']);
  }

  goBack(): void {
    if (this.success) {
      this.success = null;
      window.scrollTo({ top: 0 });
    } else {
      void this.router.navigate(['/home']);
    }
  }

  private async restoreGuest(): Promise<void> {
    try {
      const guest = (await this.storage.get('seven:guest')) as { name?: string; phone?: string } | null;
      if (guest?.name) {
        this.name.setValue(guest.name);
      }
      if (guest?.phone) {
        this.phone.setValue(guest.phone);
      }
    } catch {
      // Storage vacío o no disponible — se dejan los valores por defecto.
    }
  }

  /** En nativo, confirma con una notificación local inmediata la solicitud. */
  private async notifyCreated(): Promise<void> {
    await this.native.notify(
      'Solicitud enviada',
      `${this.title}. Te avisaremos al confirmarse (código ${this.success?.bookingCode ?? ''}).`,
      1000 + this.bookableId,
    );
  }

  /** Devuelve el valor solo si pertenece a la lista blanca; si no, null. */
  private oneOf<T>(value: string | null, allowed: readonly T[]): T | null {
    return allowed.find((candidate) => candidate === value) ?? null;
  }

  private oneHourFromNow(): Date {
    const d = new Date();
    d.setHours(d.getHours() + 1, 0, 0, 0);
    return d;
  }

  private tomorrowAtHour(hour: number): Date {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    d.setHours(hour, 0, 0, 0);
    return d;
  }

  /** Convierte una fecha a valor de <input type="datetime-local">. */
  private toLocalInput(date: Date): string {
    const pad = (n: number): string => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }
}