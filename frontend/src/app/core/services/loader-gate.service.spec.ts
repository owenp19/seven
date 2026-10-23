import { TestBed } from '@angular/core/testing';

import { LoaderGateService } from './loader-gate.service';

describe('LoaderGateService', () => {
  let service: LoaderGateService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(LoaderGateService);
  });

  it('no salta nada por defecto', () => {
    expect(service.consume()).toBe(false);
  });

  it('salta exactamente una navegación tras skipNext', () => {
    service.skipNext();

    expect(service.consume()).toBe(true);
    expect(service.consume()).toBe(false);
  });

  it('acumula saltos: dos skipNext cubren dos navegaciones', () => {
    service.skipNext();
    service.skipNext();

    expect(service.consume()).toBe(true);
    expect(service.consume()).toBe(true);
    expect(service.consume()).toBe(false);
  });

  it('nunca deja el contador en negativo', () => {
    expect(service.consume()).toBe(false);
    expect(service.consume()).toBe(false);
    expect(service.consume()).toBe(false);
  });
});
