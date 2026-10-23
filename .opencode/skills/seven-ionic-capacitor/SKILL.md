---
name: seven-ionic-capacitor
description: Patrones Ionic 9 y Capacitor 8 de SEVEN. Usar al tocar pantallas Ionic, navegación, safe areas, plugins nativos, offline, splash, status bar o el proyecto Android/iOS. Incluye el estado real de sincronización nativa, permisos Android pendientes y reglas para no romper WebView ni builds.
---

# SEVEN Ionic + Capacitor

## Versiones
- `@ionic/angular` `^9.0.0`, Angular 21, `@capacitor/core`/`cli` `^8.5.1`.
- `webDir: 'www'`, `appId: 'com.seven.app'`, `androidScheme: 'https'`.
- Android: `minSdk 24`, `compileSdk/targetSdk 36`, Gradle 8.14.3, AGP 8.13.0.
- iOS: proyecto presente en `frontend/ios/`.

## Shell y navegación
- IonRouterOutlet + tabs en `layouts/tabs/tabs-layout.component.ts`.
- Swipe-back habilitado; Android usa botón físico.
- Respetar `--seven-bottom-nav-height` y las safe areas en tab bar y action bars.
- No usar modales/popovers que tapen el notch sin `--offset-top`.

## Plugins instalados
`@capacitor/geolocation`, `haptics`, `local-notifications`, `network`,
`preferences`, `share`, `splash-screen`, `status-bar`.

## Estado nativo real (importante)
`frontend/android/app/src/main/assets/capacitor.plugins.json` solo contiene
`haptics`, `splash-screen` y `status-bar`. Faltan `geolocation`,
`local-notifications`, `network`, `preferences` y `share` en el proyecto Android
hasta ejecutar `npx cap sync` (no ejecutarlo en una auditoría).
Además, `AndroidManifest.xml` solo declara `INTERNET`; para geolocalización y
notificaciones locales faltan permisos explícitos (`ACCESS_FINE_LOCATION`,
`POST_NOTIFICATIONS`, etc.) y su flujo de solicitud en runtime.

## Reglas
1. Comprobar `Capacitor.isNativePlatform()` antes de usar un plugin; el servicio
   `native.service.ts` ya centraliza los fallbacks web.
2. Nunca llamar a plugins directamente desde páginas si existe `NativeService`.
3. Splash: lo oculta la app cuando baja el loader global; `launchAutoHide: false`.
   No cambiar a auto-hide sin revisar `app.component.ts` (watchdog de 5 s).
4. Status bar: `overlaysWebView: true`; el contenido debe respetar safe areas.
5. Offline: usar `connectivity`, `data-cache` y `offline-download`; no detectar
   red con `navigator.onLine` a mano.
6. No añadir plugins sin comprobar que están en `package.json` y sincronizados.
7. No editar archivos generados de `android/`/`ios/` salvo que el cambio sea
   nativo deliberado; documentarlo.

## Comandos
```powershell
cd frontend
npm run build          # genera www
npx cap sync           # sincroniza web + plugins (modifica nativo)
npx cap open android   # requiere Android Studio (no usar en Fase 0)
npx cap run android    # requiere SDK/dispositivo
```
No generar APK ni abrir Android Studio dentro de esta fase.

## Diagnóstico móvil
- JDK detectado: Temurin 21.
- `ANDROID_HOME` / `ANDROID_SDK_ROOT`: no detectados en el entorno actual.
- El build nativo no se verificó; no afirmar que compila.
- Revisar `frontend/android/app/src/main/assets/public` solo como artefacto;
  se regenera con `npm run build` + `npx cap sync`.
