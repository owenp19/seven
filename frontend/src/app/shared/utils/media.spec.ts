import { mediaSources } from './media';

describe('mediaSources', () => {
  it('conserva el orden original', () => {
    expect(mediaSources('c.jpg', 'a.jpg', 'b.jpg')).toEqual(['c.jpg', 'a.jpg', 'b.jpg']);
  });

  it('descarta entradas vacías, en blanco y nulas', () => {
    expect(mediaSources('a.jpg', '', '   ', null, undefined, 'b.jpg')).toEqual(['a.jpg', 'b.jpg']);
  });

  it('elimina duplicados', () => {
    expect(mediaSources('a.jpg', 'b.jpg', 'a.jpg')).toEqual(['a.jpg', 'b.jpg']);
  });

  it('recorta espacios antes de comparar', () => {
    expect(mediaSources(' a.jpg ', 'a.jpg')).toEqual(['a.jpg']);
  });

  it('devuelve una lista vacía cuando no hay nada utilizable', () => {
    expect(mediaSources()).toEqual([]);
    expect(mediaSources('', null, undefined)).toEqual([]);
  });

  it('no muta la entrada', () => {
    const input = ['a.jpg', 'a.jpg'];
    const result = mediaSources(...input);

    expect(result).not.toBe(input);
    expect(input).toEqual(['a.jpg', 'a.jpg']);
  });
});
