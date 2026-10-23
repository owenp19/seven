import { SimpleChange, SimpleChanges } from '@angular/core';

import { RouteParam } from './route-param';

function change(): SimpleChanges {
  return { slug: new SimpleChange('a', 'b', false) } as SimpleChanges;
}

describe('RouteParam', () => {
  it('reproduce el valor actual al suscribirse', () => {
    const param = new RouteParam<string>();
    param.bind(change(), 'slug', 'a');

    const seen: string[] = [];
    param.stream$.subscribe((value) => seen.push(value));

    expect(seen).toEqual(['a']);
  });

  it('emite otra vez cuando el router reutiliza la instancia con otro slug', () => {
    const param = new RouteParam<string>();
    param.bind(change(), 'slug', 'a');

    const seen: string[] = [];
    param.stream$.subscribe((value) => seen.push(value));

    // El router reasigna el @Input sobre la MISMA instancia: esto es lo que
    // antes se ignoraba y dejaba el detalle anterior en pantalla.
    param.bind(change(), 'slug', 'b');

    expect(seen).toEqual(['a', 'b']);
  });

  it('no repite el mismo valor', () => {
    const param = new RouteParam<string>();
    param.bind(change(), 'slug', 'a');

    const seen: string[] = [];
    param.stream$.subscribe((value) => seen.push(value));

    param.bind(change(), 'slug', 'a');
    param.bind(change(), 'slug', 'a');

    expect(seen).toEqual(['a']);
  });

  it('ignora un valor vacío', () => {
    const param = new RouteParam<string>();

    const seen: string[] = [];
    param.stream$.subscribe((value) => seen.push(value));

    param.bind(change(), 'slug', undefined);
    param.bind(change(), 'slug', '');

    expect(seen).toEqual([]);
    expect(param.value).toBeNull();
  });

  it('ignora cambios de otros inputs', () => {
    const param = new RouteParam<string>();
    const changes = { id: new SimpleChange('1', '2', false) } as SimpleChanges;

    param.bind(changes, 'slug', 'a');

    expect(param.value).toBeNull();
  });

  it('deja de emitir al desuscribirse', () => {
    const param = new RouteParam<string>();
    param.bind(change(), 'slug', 'a');

    const seen: string[] = [];
    const sub = param.stream$.subscribe((value) => seen.push(value));
    sub.unsubscribe();

    param.bind(change(), 'slug', 'b');

    expect(seen).toEqual(['a']);
  });
});
