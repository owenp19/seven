import { TestBed } from '@angular/core/testing';

import { Subject } from 'rxjs';

import { ConnectivityService } from './connectivity.service';
import { NativeService } from './native.service';

const MODE_KEY = 'seven_offline_mode';
const AUTO_KEY = 'seven_offline_auto';

describe('ConnectivityService', () => {
  let network: Subject<boolean>;
  let service: ConnectivityService;

  beforeEach(() => {
    localStorage.removeItem(MODE_KEY);
    localStorage.removeItem(AUTO_KEY);
    network = new Subject<boolean>();

    TestBed.configureTestingModule({
      providers: [
        {
          provide: NativeService,
          useValue: {
            isOnlineSync: () => true,
            networkStatus$: network.asObservable(),
          },
        },
      ],
    });

    service = TestBed.inject(ConnectivityService);
  });

  afterEach(() => {
    localStorage.removeItem(MODE_KEY);
    localStorage.removeItem(AUTO_KEY);
  });

  it('arranca online cuando la interfaz de red está activa', () => {
    expect(service.isReallyOnline()).toBe(true);
    expect(service.isOnline()).toBe(true);
    expect(service.isOffline()).toBe(false);
  });

  it('sigue los cambios reales de red', () => {
    network.next(false);

    expect(service.isReallyOnline()).toBe(false);
    expect(service.isOnline()).toBe(false);
    expect(service.isOffline()).toBe(true);
  });

  it('el modo manual gana sobre la red real', () => {
    service.setManualOffline(true);

    expect(service.isOnline()).toBe(false);
    // ...pero no finge que el wifi se apagó.
    expect(service.isReallyOnline()).toBe(true);
  });

  it('vuelve a estar online al desactivar el modo manual', () => {
    service.setManualOffline(true);
    service.setManualOffline(false);

    expect(service.isOnline()).toBe(true);
  });

  it('persiste el modo manual en localStorage', () => {
    service.setManualOffline(true);
    expect(localStorage.getItem(MODE_KEY)).toBe('1');

    service.setManualOffline(false);
    expect(localStorage.getItem(MODE_KEY)).toBeNull();
  });

  it('persiste la preferencia de datos móviles', () => {
    service.setAutoOffline(true);
    expect(localStorage.getItem(AUTO_KEY)).toBe('1');

    service.setAutoOffline(false);
    expect(localStorage.getItem(AUTO_KEY)).toBeNull();
  });

  it('recupera el modo manual guardado al arrancar', () => {
    localStorage.setItem(MODE_KEY, '1');

    TestBed.resetTestingModule();
    expect(TestBed.inject(ConnectivityService).manualOffline()).toBe(true);
  });

  it('el modo automático no desconecta: solo cambia cuándo se descarga', () => {
    service.setAutoOffline(true);

    expect(service.isOnline()).toBe(true);
  });
});
