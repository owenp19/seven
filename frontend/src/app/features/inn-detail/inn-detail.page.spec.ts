import { SimpleChange, SimpleChanges } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';

import { Observable, of } from 'rxjs';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { InnDetailPage } from './inn-detail.page';
import { InnService } from '../../services/inn.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { Inn } from '../../models';

/**
 * El router no recrea el componente cuando cambia solo el param de la ruta,
 * asi que una pagina que lee `@Input() slug` una vez en `ngOnInit` se queda
 * mostrando el registro anterior. Estos tests fijan ese comportamiento.
 */
describe('InnDetailPage route reuse', () => {
  const innFor = (slug: string): Inn => ({ slug, id: 1, name: slug }) as unknown as Inn;

  let getBySlug: ReturnType<typeof vi.fn>;
  let fixture: ComponentFixture<InnDetailPage>;

  function change(slug: string): SimpleChanges {
    return { slug: new SimpleChange(slug, slug, false) } as SimpleChanges;
  }

  beforeEach(async () => {
    getBySlug = vi.fn((slug: string) => of(innFor(slug)));

    await TestBed.configureTestingModule({
      providers: [
        { provide: InnService, useValue: { getBySlug, getAll: () => of([]) } },
        { provide: HapticsService, useValue: { selection: () => Promise.resolve() } },
        { provide: NativeService, useValue: { share: () => Promise.resolve() } },
        {
          provide: FavoritesService,
          useValue: {
            favorites$: of([]),
            track: () => Promise.resolve(),
            isFavorite: () => Promise.resolve(false),
            toggle: () => Promise.resolve(false),
          },
        },
        { provide: Router, useValue: { navigate: () => Promise.resolve(true) } },
      ],
    })
      .overrideComponent(InnDetailPage, { set: { template: '' } })
      .compileComponents();

    fixture = TestBed.createComponent(InnDetailPage);
  });

  it('pide la posada del slug inicial', () => {
    const page = fixture.componentInstance;

    page.slug = 'casa-del-puerto';
    page.ngOnChanges(change('casa-del-puerto'));
    page.ngOnInit();

    expect(getBySlug).toHaveBeenCalledTimes(1);
    expect(getBySlug).toHaveBeenCalledWith('casa-del-puerto');
  });

  it('vuelve a pedir cuando el router reutiliza la instancia con otro slug', () => {
    const page = fixture.componentInstance;

    page.slug = 'casa-del-puerto';
    page.ngOnChanges(change('casa-del-puerto'));
    page.ngOnInit();

    page.slug = 'posada-la-lua';
    page.ngOnChanges(change('posada-la-lua'));

    expect(getBySlug).toHaveBeenCalledTimes(2);
    expect(getBySlug).toHaveBeenLastCalledWith('posada-la-lua');
  });

  it('emite el registro nuevo, no el anterior, al cambiar de slug', () => {
    const page = fixture.componentInstance;
    const seen: (string | null)[] = [];

    page.slug = 'casa-del-puerto';
    page.ngOnChanges(change('casa-del-puerto'));
    page.ngOnInit();

    const stream: Observable<Inn | null> = page.inn$;
    stream.subscribe((inn) => seen.push(inn ? inn.slug : null));

    page.slug = 'posada-la-lua';
    page.ngOnChanges(change('posada-la-lua'));

    expect(seen).toEqual(['casa-del-puerto', 'posada-la-lua']);
  });

  it('no vuelve a pedir si el slug no cambio de verdad', () => {
    const page = fixture.componentInstance;

    page.slug = 'casa-del-puerto';
    page.ngOnChanges(change('casa-del-puerto'));
    page.ngOnInit();

    page.ngOnChanges(change('casa-del-puerto'));

    expect(getBySlug).toHaveBeenCalledTimes(1);
  });
});
