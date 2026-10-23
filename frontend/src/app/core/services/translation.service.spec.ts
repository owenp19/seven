import { TestBed } from '@angular/core/testing';

import { TranslationService } from './translation.service';

describe('TranslationService', () => {
  const STORAGE_KEY = 'seven_language';

  let service: TranslationService;

  beforeEach(() => {
    localStorage.removeItem(STORAGE_KEY);
    document.documentElement.removeAttribute('lang');

    TestBed.configureTestingModule({});
    service = TestBed.inject(TranslationService);
  });

  afterEach(() => {
    localStorage.removeItem(STORAGE_KEY);
  });

  it('arranca en español cuando no hay preferencia guardada', () => {
    expect(service.language()).toBe('es');
    expect(service.t('menu.settings')).toBe('Configuración');
  });

  it('ofrece español, inglés y portugués en ese orden', () => {
    expect(service.languages.map((option) => option.code)).toEqual(['es', 'en', 'pt']);
  });

  it('traduce la misma clave a los tres idiomas', () => {
    service.setLanguage('en');
    expect(service.t('menu.settings')).toBe('Settings');

    service.setLanguage('pt');
    expect(service.t('menu.settings')).toBe('Configurações');
  });

  it('expone las tres claves de menú con la misma forma en cada idioma', () => {
    for (const code of ['es', 'en', 'pt'] as const) {
      service.setLanguage(code);
      for (const key of ['menu.news', 'menu.settings', 'menu.about'] as const) {
        expect(service.t(key).length).toBeGreaterThan(0);
      }
    }
  });

  it('rellena los placeholders {param}', () => {
    expect(service.t('settings.version', { version: '1.2.3' })).toBe('Versión 1.2.3');

    service.setLanguage('en');
    expect(service.t('settings.version', { version: '1.2.3' })).toBe('Version 1.2.3');
  });

  it('deja el placeholder visible si falta un parámetro, en vez de romperse', () => {
    expect(service.t('settings.version')).toBe('Versión {version}');
  });

  it('persiste la preferencia en localStorage', () => {
    service.setLanguage('pt');
    expect(localStorage.getItem(STORAGE_KEY)).toBe('pt');
  });

  it('es idempotente: fijar el idioma actual no lo cambia ni re-sincroniza el DOM', () => {
    service.setLanguage('en');
    document.documentElement.lang = 'en';

    service.setLanguage('en');

    expect(service.language()).toBe('en');
    expect(document.documentElement.lang).toBe('en');
    expect(service.isActive('en')).toBe(true);
    expect(service.isActive('pt')).toBe(false);
  });

  it('refleja el idioma en el atributo lang del documento', () => {
    service.setLanguage('en');
    expect(document.documentElement.lang).toBe('en');

    service.setLanguage('pt');
    expect(document.documentElement.lang).toBe('pt');
  });

  it('recupera un idioma guardado válido', () => {
    localStorage.setItem(STORAGE_KEY, 'pt');

    TestBed.resetTestingModule();
    expect(TestBed.inject(TranslationService).language()).toBe('pt');
  });

  it('ignora un valor guardado corrupto y cae a español', () => {
    localStorage.setItem(STORAGE_KEY, 'klingon');

    TestBed.resetTestingModule();
    expect(TestBed.inject(TranslationService).language()).toBe('es');
  });
});
