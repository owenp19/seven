# SEVEN — Stack tecnológico

## Frontend
| Tecnología | Versión | Notas |
|------------|---------|-------|
| Angular | `~21.0.0` | Standalone, signals, builder `@angular/build:application` |
| TypeScript | `~5.9.0` | Strict por configuración del proyecto |
| Ionic Angular | `^9.0.0` | Componentes y navegación |
| Capacitor Core/CLI | `^8.5.1` | Android/iOS, `webDir: www` |
| RxJS | `^7.8.0` | Flujos HTTP/eventos |
| Zone.js | `~0.15.0` | Change detection con coalescing |
| Vitest | `^4.0.8` | Tests vía Angular CLI |
| ESLint + angular-eslint | `^9` / `~21` | Lint |

### Plugins Capacitor
`@capacitor/geolocation`, `haptics`, `local-notifications`, `network`,
`preferences`, `share`, `splash-screen`, `status-bar`.
`@ionic/storage ^4` para persistencia web/nativa.

### Scripts
```powershell
cd frontend
npm start        # ng serve
npm run build    # ng build → www
npm run lint
npm test
npm run mobile:sync   # npx cap sync
```

## Backend
| Tecnología | Versión | Notas |
|------------|---------|-------|
| PHP | `^8.2` | Local 8.2.12 (XAMPP) |
| Laravel | `^11.31` | API REST |
| Sanctum | `^4.0` | Tokens personales y abilities |
| L5-Swagger | `^11.1` | Documentación opcional |
| PHPUnit | `^11.0.1` | Feature/Unit |
| Laravel Pint | `^1.13` | Estilo |
| MySQL/MariaDB | MariaDB 10.4.32 local | BD `seven_db` |

### Scripts
```powershell
cd backend
php artisan serve --port=8000
php artisan test
vendor\bin\pint --test
php artisan route:list
```

## Raíz del monorepo
| Herramienta | Versión |
|-------------|---------|
| Node.js | v22.15.0 |
| npm | 11.19.1 |
| Composer | 2.8.8 |
| Git | 2.54.0 |
| JDK | Temurin 21.0.12 |
| Gradle (wrapper) | 8.14.3 |
| Android Gradle Plugin | 8.13.0 |

## Entornos
- `frontend/src/environments/environment.ts`: API local
  `http://127.0.0.1:8000/api`, versión `v1`.
- `environment.prod.ts`: host placeholder `https://api.seven.app/api`; la app
  aborta al arrancar hasta configurar un host real.
- Backend: `APP_TIMEZONE=America/Bogota`, locale `es`, moneda `COP`.

## Herramientas de desarrollo
- OpenCode 1.19.0 con skills locales y Context7 MCP.
- Skills externas instaladas con `npx skills` en `.agents/skills/`.
- No hay CI configurado en el repositorio (no se encontró pipeline).
