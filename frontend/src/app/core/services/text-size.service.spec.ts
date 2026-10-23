import { TestBed } from '@angular/core/testing';

import { TextSizeService } from './text-size.service';
import type { TextSizeId } from './text-size.service';

const STORAGE_KEY = 'seven_text_size';

describe('TextSizeService', () => {
  let service: TextSizeService;

  beforeEach(() => {
    localStorage.removeItem(STORAGE_KEY);
    document.documentElement.style.fontSize = '';
    document.documentElement.style.removeProperty('--ion-font-size');

    TestBed.configureTestingModule({});
    service = TestBed.inject(TextSizeService);
  });

  afterEach(() => {
    localStorage.removeItem(STORAGE_KEY);
    document.documentElement.style.fontSize = '';
    document.documentElement.style.removeProperty('--ion-font-size');
  });

  it('arranca en normal sin preferencia guardada', () => {
    expect(service.textSize()).toBe('normal');
    expect(service.isActive('normal')).toBe(true);
  });

  it('ofrece los cuatro niveles de tamaño', () => {
    expect(service.options.map((option) => option.id)).toEqual(['small', 'normal', 'large', 'xlarge']);
  });

  it('mantiene las escalas ordenadas de menor a mayor', () => {
    const scales = service.options.map((option) => option.scale);

    for (let i = 1; i < scales.length; i++) {
      expect(scales[i]).toBeGreaterThan(scales[i - 1]);
    }
  });

  it('el nivel normal deja 1rem valiendo 16px', () => {
    service.setTextSize('small');
    service.setTextSize('normal');

    // Es la referencia de diseño: en "normal" nada cambia respecto a antes.
    expect(document.documentElement.style.fontSize).toBe('16px');
  });

  it('escala la raíz, que es lo que hace crecer todo el texto en rem', () => {
    service.setTextSize('xlarge');

    expect(document.documentElement.style.fontSize).toBe('20px');
  });

  it('reduce la raíz en el nivel pequeño', () => {
    service.setTextSize('small');

    expect(document.documentElement.style.fontSize).toBe('14px');
  });

  it('ajusta también el token de Ionic, si no los componentes no escalan', () => {
    service.setTextSize('large');

    // Sin esto, toasts, alerts e inputs se quedan en 16px fijos y la
    // opción parecería medio rota.
    expect(document.documentElement.style.getPropertyValue('--ion-font-size')).toBe('18px');
  });

  it('persiste el nivel elegido', () => {
    service.setTextSize('large');

    expect(localStorage.getItem(STORAGE_KEY)).toBe('large');
  });

  it('recupera un nivel guardado válido al arrancar', () => {
    localStorage.setItem(STORAGE_KEY, 'xlarge');

    TestBed.resetTestingModule();
    expect(TestBed.inject(TextSizeService).textSize()).toBe('xlarge');
  });

  it('ignora un valor guardado corrupto y cae a normal', () => {
    localStorage.setItem(STORAGE_KEY, 'gigante');

    TestBed.resetTestingModule();
    expect(TestBed.inject(TextSizeService).textSize()).toBe('normal');
  });

  it('es idempotente: fijar el nivel actual no reescribe nada', () => {
    service.setTextSize('normal');

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull();
  });

  it('cambia entre los cuatro niveles en cualquier orden', () => {
    const order: TextSizeId[] = ['xlarge', 'small', 'large', 'normal', 'small'];

    for (const id of order) {
      service.setTextSize(id);

      expect(service.textSize()).toBe(id);
      expect(service.isActive(id)).toBe(true);
    }
  });

  it('solo un nivel está activo a la vez', () => {
    service.setTextSize('large');

    const active = service.options.filter((option) => service.isActive(option.id));

    expect(active).toHaveLength(1);
    expect(active[0].id).toBe('large');
  });
});
