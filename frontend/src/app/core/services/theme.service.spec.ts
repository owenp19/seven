import { TestBed } from '@angular/core/testing';

import { ThemeService } from './theme.service';
import type { ThemeId } from './theme.service';

const STORAGE_KEY = 'seven_theme';

describe('ThemeService', () => {
  let service: ThemeService;

  beforeEach(() => {
    localStorage.removeItem(STORAGE_KEY);
    document.documentElement.removeAttribute('data-seven-theme');

    TestBed.configureTestingModule({});
    service = TestBed.inject(ThemeService);
  });

  afterEach(() => {
    localStorage.removeItem(STORAGE_KEY);
    document.documentElement.removeAttribute('data-seven-theme');
  });

  it('arranca en azul (el tema de marca) sin preferencia guardada', () => {
    expect(service.theme()).toBe('ocean');
    expect(service.isActive('ocean')).toBe(true);
  });

  it('ofrece exactamente los tres temas pedidos', () => {
    expect(service.options.map((option) => option.id)).toEqual(['ocean', 'sun', 'coral']);
  });

  it('escribe el atributo en <html> para que CSS aplique los tokens', () => {
    expect(document.documentElement.getAttribute('data-seven-theme')).toBe('ocean');

    service.setTheme('sun');
    expect(document.documentElement.getAttribute('data-seven-theme')).toBe('sun');

    service.setTheme('coral');
    expect(document.documentElement.getAttribute('data-seven-theme')).toBe('coral');
  });

  it('persiste el tema en localStorage', () => {
    service.setTheme('coral');

    expect(localStorage.getItem(STORAGE_KEY)).toBe('coral');
  });

  it('recupera un tema guardado válido en el siguiente arranque', () => {
    localStorage.setItem(STORAGE_KEY, 'sun');

    TestBed.resetTestingModule();
    expect(TestBed.inject(ThemeService).theme()).toBe('sun');
  });

  it('ignora un valor guardado corrupto y cae a azul', () => {
    localStorage.setItem(STORAGE_KEY, 'fucsia');

    TestBed.resetTestingModule();
    expect(TestBed.inject(ThemeService).theme()).toBe('ocean');
  });

  it('es idempotente: fijar el tema actual no reescribe nada', () => {
    service.setTheme('ocean');

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull();
    expect(service.isActive('ocean')).toBe(true);
  });

  it('expone un color de muestra por tema para la vista previa', () => {
    for (const option of service.options) {
      expect(option.swatch).toMatch(/^#[0-9A-F]{6}$/i);
      expect(option.onSwatch).toMatch(/^#[0-9A-F]{6}$/i);
    }
  });

  it('mantiene los tres temas distinguibles entre sí', () => {
    const swatches = service.options.map((option) => option.swatch);

    expect(new Set(swatches).size).toBe(3);
  });

  it('cambia entre los tres temas en cualquier orden', () => {
    const order: ThemeId[] = ['sun', 'ocean', 'coral', 'sun'];

    for (const id of order) {
      service.setTheme(id);
      expect(service.theme()).toBe(id);
      expect(document.documentElement.getAttribute('data-seven-theme')).toBe(id);
    }
  });
});
