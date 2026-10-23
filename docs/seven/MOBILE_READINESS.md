# SEVEN — Mobile readiness

> Diagnóstico de solo lectura. No se ejecutó `cap sync`, no se abrió Android
> Studio y no se generó APK.

## Configuración
| Elemento | Valor |
|----------|-------|
| App ID | `com.seven.app` |
| App name | `SEVEN` |
| `webDir` | `www` |
| Android scheme | `https` |
| minSdk | 24 |
| compileSdk / targetSdk | 36 / 36 |
| Gradle wrapper | 8.14.3 |
| AGP | 8.13.0 |
| JDK | Temurin 21.0.12 |
| Splash | Manual (`launchAutoHide: false`), fondo `#0B2D4A` |
| Status bar | Overlay, estilo `DARK`, fondo `#0B2D4A` |

## Proyectos nativos
- `frontend/android/` existe y está configurado.
- `frontend/ios/` existe; no verificable en Windows.
- `frontend/android/app/src/main/assets/public/` contiene un build web previo.

## Hallazgos
### 1. Plugins nativos desincronizados — Alto
`package.json` declara 8 plugins, pero
`frontend/android/app/src/main/assets/capacitor.plugins.json` solo registra:
`@capacitor/haptics`, `@capacitor/splash-screen`, `@capacitor/status-bar`.
Faltan en el proyecto Android:
`@capacitor/geolocation`, `@capacitor/local-notifications`, `@capacitor/network`,
`@capacitor/preferences`, `@capacitor/share`.
Impacto: esos plugins pueden fallar o no estar disponibles en Android hasta
`npx cap sync`.

### 2. Permisos Android incompletos — Alto
`AndroidManifest.xml` solo declara `INTERNET`.
- Geolocalización requiere `ACCESS_COARSE_LOCATION` / `ACCESS_FINE_LOCATION`
  (y `ACCESS_BACKGROUND_LOCATION` solo si se justifica).
- Notificaciones locales en Android 13+ requieren `POST_NOTIFICATIONS`.
- Si se usa share/file provider, revisar `FileProvider` y `file_paths.xml`.
Impacto: `Geolocation.getCurrentPosition()` y `LocalNotifications` pueden fallar
o no pedir permiso correctamente.

### 3. SDK de Android no detectado — Medio
`ANDROID_HOME` y `ANDROID_SDK_ROOT` están vacíos en el entorno actual.
Impacto: un build nativo probablemente falle hasta configurar el SDK.

### 4. Build web de producción bloqueado — Medio
`environment.prod.ts` usa `https://api.seven.app/api` como placeholder y
`assertApiConfigured()` lanza al arrancar. Es intencional, pero impide release.

### 5. Sin verificación de build — Informativo
No se ejecutó `npm run build`, `npx cap sync` ni Gradle. No afirmar que compila.

## Checklist para habilitar Android (futuro, con aprobación)
1. Configurar `ANDROID_HOME` y `sdkmanager` (API 36, build-tools).
2. `cd frontend; npm run build`.
3. `npx cap sync android`.
4. Añadir permisos justificados al `AndroidManifest.xml`.
5. Implementar solicitud runtime de permisos antes de usar geolocalización o
   notificaciones.
6. Revisar `network_security_config` si se usa HTTP en desarrollo.
7. Probar en dispositivo/emulador: arranque, splash, status bar, back button,
   safe areas, teclado, offline, share, hápticos.
8. Definir iconos/splash con `@capacitor/assets`.
9. Firmar release y guardar keystore fuera del repo.

## Safe areas y UI
- Respetar `env(safe-area-inset-*)` en headers, tab bar y action bars.
- `--seven-bottom-nav-height` no incluye el inset; sumarlo.
- Evitar `position: fixed` pegado al borde inferior sin inset.
- Probar notch, punch-hole y gesture bar.

## Comandos
```powershell
cd frontend
npm run build
npx cap sync
npx cap doctor
```
No ejecutar `cap open`/`cap run` ni generar APK en esta fase.

## Estado
**No listo para release móvil** hasta resolver sincronización, permisos,
host de producción y verificación de build.
