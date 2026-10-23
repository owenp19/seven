---
name: seven-angular
description: Convenciones Angular 21 de SEVEN. Usar al crear o modificar componentes, páginas, rutas, servicios, interceptores, estado con signals, formularios o tests del frontend. Explica standalone components, lazy loading, inyección con inject, cambio de estado con signals, HTTP con interceptores y las trampas específicas del repo.
---

# SEVEN Angular

## Versión y bootstrap
- Angular `~21.0.0`, TypeScript `~5.9.0`, builder `@angular/build:application`.
- Bootstrap standalone en `frontend/src/app/app.config.ts`.
- `provideZoneChangeDetection({ eventCoalescing: true })`.
- Router con `withComponentInputBinding()` y `SelectivePreloadStrategy`.
- HTTP: `provideHttpClient(withFetch(), withInterceptors([apiRequestInterceptor, loadingInterceptor, errorInterceptor]))`.
- Ionic: `provideIonicAngular({ swipeBackEnabled: true, backButtonText: '' })`.
- Storage: `Storage({ name: 'seven-db', storeName: 'seven_kv' })` bajo el token `STORAGE`.

## Estructura
```
src/app/
  core/config|constants|services|tokens/
  features/<área>/<área>.page.ts|html|scss|spec.ts
  guards/ interceptors/ interfaces/ layouts/ models/ services/ shared/ utils/
```
Toda página es standalone y lazy. Los componentes compartidos viven en
`shared/components/`; no duplicar tarjetas/headers/nav.

## Patrones obligatorios
1. Componentes **standalone**; no `NgModule`.
2. Inyección con `inject()` en propiedades `private readonly`.
3. Estado local con `signal()`, `computed()` y `effect()` solo cuando haga falta.
4. Plantillas con `@if`, `@for (item of items(); track item.id)`, `@switch`.
5. Suscripciones con `takeUntilDestroyed(inject(DestroyRef))` o `toSignal`.
6. Rutas con `loadComponent: () => import(...)`; no componentes eager en `routes.ts`.
7. `ChangeDetectionStrategy.OnPush` en componentes nuevos o al refactorizar.
8. Formularios reactivos tipados; validación en cliente + errores del API.
9. URLs de API solo desde `environment.apiUrl` + `apiVersion`; nunca hardcodear.
10. Errores del API se normalizan en `error.interceptor.ts`; no mostrar `HttpErrorResponse` crudo.

## Servicios clave
- `core/services/api-client.service.ts`: contrato HTTP y versión.
- `bookings-store`, `favorites`, `data-cache`, `connectivity`, `offline-download`,
  `loading`, `theme`, `translation`, `text-size`, `native`, `haptics`, `data-privacy`.
- Reutilizar antes de crear. Si un servicio crece demasiado, extraer lógica pura a `utils/`.

## Rutas
- Tab shell: `layouts/tabs/tabs-layout.component.ts` con `home`, `explore`,
  `bookings`, `assistant`, `saved`, `contact`.
- Detalles fuera del shell: `business/:id`, `tour/:slug`, `restaurant/:slug`,
  `inn/:slug`, `booking/new`, `noticias/:slug`.
- Fallback `**` redirige a `/home`. Mantener títulos en `data.title`.

## Testing
- Runner: Vitest vía Angular CLI (`npm test`).
- Specs junto al archivo (`*.spec.ts`).
- Mockear servicios con `{ provide: X, useValue: {...} }`.
- Probar lógica de signals y estados de error/carga, no solo el render feliz.
- No usar `fixture.detectChanges()` sin necesidad; preferir `await fixture.whenStable()`.

## Trampas conocidas
- `localStorage` puede no existir o fallar en WebView; usar los servicios nativos.
- No usar `any` para esquivar tipos del API; actualizar `models/` e `interfaces/`.
- Evitar lógica de negocio en plantillas; moverla a `computed()` o al servicio.
- No romper la estrategia de preload: solo marcar `data: { preload: true }` en rutas de arranque/colecciones.
- Los imports de iconos son explícitos: registrar el icono en `register-icons.ts`.

## Comandos
```powershell
cd frontend
npm start        # ng serve
npm run build    # ng build (webDir www)
npm run lint
npm test
```
