# SEVEN — Más que un destino

<!-- markdownlint-disable MD033 -->
<p align="center">
  <img src="frontend/src/assets/img/brand/seven-logo-vertical-blanco.png" alt="SEVEN" width="200">
</p>

<div align="center">
  <img
    src="frontend/src/assets/img/backgounds/sai-bg.jpg"
    alt="Portada SEVEN"
    width="24%"
    style="max-width: 240px; vertical-align: top; margin: 0 8px;"
  />
  <img
    src="frontend/src/assets/img/tourism/gastronomy/gastronomia.jpg"
    alt="Gastronomía"
    width="24%"
    style="max-width: 240px; vertical-align: top; margin: 0 8px;"
  />
  <img
    src="frontend/src/assets/img/tourism/accommodations/posadas.jpg"
    alt="Posadas nativas"
    width="24%"
    style="max-width: 240px; vertical-align: top; margin: 0 8px;"
  />
  <img
    src="frontend/src/assets/img/tourism/experiences/welcome-island.jpg"
    alt="Experiencias"
    width="24%"
    style="max-width: 240px; vertical-align: top; margin: 0 8px;"
  />
</div>
<!-- markdownlint-enable MD033 -->

Plataforma de turismo que conecta a los visitantes con los servicios auténticos de San Andrés Islas: gastronomía, posadas nativas y experiencias locales. Incluye el frontend móvil (Ionic + Angular + Capacitor) y el backend de API (Laravel).

## Tecnologias

### Frontend movil (Ionic + Angular)

[![Angular](https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white)](https://angular.dev)
[![Ionic](https://img.shields.io/badge/Ionic-3880FF?style=for-the-badge&logo=ionic&logoColor=white)](https://ionicframework.com)
[![TypeScript](https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Sass](https://img.shields.io/badge/Sass-CC6699?style=for-the-badge&logo=sass&logoColor=white)](https://sass-lang.com)
[![Capacitor](https://img.shields.io/badge/Capacitor-119EFF?style=for-the-badge&logo=capacitor&logoColor=white)](https://capacitorjs.com)
[![RxJS](https://img.shields.io/badge/RxJS-BE322B?style=for-the-badge&logo=reactivex&logoColor=white)](https://rxjs.dev)

### Backend (Laravel API REST)

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)](https://getcomposer.org)
[![Sanctum](https://img.shields.io/badge/Sanctum-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/11.x/sanctum)
[![Swagger](https://img.shields.io/badge/Swagger-85EA2D?style=for-the-badge&logo=Swagger&logoColor=black)](https://swagger.io)
[![Pint](https://img.shields.io/badge/Pint-F26B38?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/11.x/pint)

### Herramientas

[![Git](https://img.shields.io/badge/Git-F05032?style=for-the-badge&logo=git&logoColor=white)](https://git-scm.com)

---

## Stack tecnologico

| Capa                | Tecnologia                                                                    | Version                    |
| ------------------- | ----------------------------------------------------------------------------- | -------------------------- |
| **Frontend**        | Ionic + Angular (standalone, signals, control flow nativo)                    | Ionic 9 / Angular 21       |
| **Lenguaje**        | TypeScript                                                                    | 5.9                        |
| **Backend**         | Laravel (API REST versionada)                                                 | Laravel 11 / PHP 8.2       |
| **Base de datos**   | MySQL / MariaDB                                                               | 8+ / 10.4+                 |
| **Autenticacion**   | v1 anonima (sin login) + backoffice con usuarios y roles (Laravel Sanctum)  | -                          |
| **Empaquetado**     | Capacitor                                                                     | Capacitor 8                |
| **Cache / estado**  | RxJS + `DataCacheService` (Ionic Storage)                                     | -                          |
| **Plataformas**     | Android + iOS (Capacitor), ademas web/PWA                                    | -                          |
| **Formato backend** | Laravel Pint                                                                  | -                          |
| **Documentacion API** | OpenAPI / Swagger (`darkaonline/l5-swagger`)                                | -                          |

---

## Estructura del proyecto

```text
seven-app/
  frontend/                      # Frontend Ionic/Angular
    src/
      app/
        core/                    # Infraestructura transversal
          config/                #   app-config.interface (contrato via APP_CONFIG)
          services/              #   api-client, cache (DataCacheService), haptics,
          │                      #   native, storage, home-data-seeder, bookings-store
          tokens/                #   APP_CONFIG, STORAGE
        features/                # Modulos lazy-load (20 paginas)
          welcome/               #   Pantalla de bienvenida
          home/                   #   Inicio (banners, categorias, destacados)
          explore/                #   Explorador con buscador y filtro por categoria
          bookings/               #   Historial de reservas del dispositivo
          booking-form/           #   Reserva anonima (sin login)
          tours/                  #   Coleccion de tours
          tour-detail/            #   Detalle de tour + CTA de reserva
          restaurants/            #   Coleccion de restaurantes
          restaurant-detail/      #   Detalle de restaurante (menu de productos)
          inns/                   #   Coleccion de posadas nativas
          inn-detail/             #   Detalle de posada (habitaciones)
          promotions/             #   Promociones
          business-detail/        #   Negocio clasico + reserva de mesa
          saved/                  #   Guardados del dispositivo
          assistant/              #   Asistente/concierge IA sobre el catalogo real
          contact/                #   Hub del viajero (perfil, asistente, accesos, ayuda)
          news/                   #   Noticias
          settings/               #   Configuracion (idioma, tema, tamano, datos)
          about/                  #   Acerca de
          privacy/                #   Politica de Privacidad
        layouts/                  # tabs-layout (shell con pestanas)
        guards/                   # feature-flag.guard
        interceptors/             # api-request, error, loading
        interfaces/               # Envelope API, Paginated, AsyncState, ApiError
        models/                   # Modelos de dominio + fromApi()
        services/                 # 9 servicios de datos (cache + paginacion)
        shared/                   # components, pipes, directives, icons, constants
      assets/                     # img/ (brand, tourism, backgrounds), icons/
      environments/               # environment.ts, environment.prod.ts
      theme/                      # Tokens SCSS (--go-*, --seven-*)
  backend/                        # Backend Laravel (API REST)
    app/
      Http/
        Controllers/Api/V1/       # Publicos (27 rutas) + Admin (63 rutas)
        Controllers/Api/V1/Admin/ #   CRUD generico + media + bookings
        Middleware/               #   AdminAuthMiddleware, EnsureUserHasRole,
        │                         #   AdminTokenMiddleware (legacy), ApiVersion
        Requests/V1/              #   FormRequests (StoreBooking, AdminLogin, etc.)
        Resources/V1/             #   Resources (envelope de respuesta)
      Models/                     # Modelos Eloquent
      Repositories/               # Consultas de listado + Concerns/AppliesTextSearch
      Services/                   # Lógica de dominio (BookingService, etc.)
      Helpers/helpers.php        # apiSuccess/apiError/apiPerPage/mediaUrl
    bootstrap/app.php             # Rutas, middleware, excepciones
    config/api.php                # Prefijo, paginacion, auth del backoffice, busqueda
    database/
      migrations/                 # Migraciones de la BD
      seeders/                    # Role, Category, DemoData, Marketplace
    routes/api/v1/api.php         # Contrato de la API
  docs/
    ARCHITECTURE.md               # Modelado y decisiones
    API.md                        # Contrato HTTP
```

---

## Comandos principales

### Frontend + backend (un solo comando)

```bash
npm run dev        # backend Laravel (127.0.0.1:8000) + frontend (localhost:4200) en paralelo
npm run setup      # instala dependencias, genera APP_KEY y siembra la BD
npm run test       # PHPUnit + Angular tests en paralelo
```

### Backend (Laravel)

```powershell
cd backend
composer install                          # solo la primera vez
copy .env.example .env                    # ajusta DB_USERNAME / DB_PASSWORD
php artisan key:generate
php artisan storage:link                  # expone storage/app/public en public/storage
php artisan migrate:fresh --seed          # tablas + datos semilla
php artisan serve --host=127.0.0.1 --port=8000

vendor\bin\phpunit                        # 74 tests / 335 aserciones
vendor\bin\pint                           # fija el estilo
vendor\bin\pint --test                    # verifica el estilo sin escribir
```

> **Usa `127.0.0.1`, no `localhost`**: `php artisan serve` vincula solo IPv4, y en el
> navegador `localhost` puede resolver a `::1` (IPv6) y los requests fallan.

Para levantar el marketplace de demo con fotos reales de la isla:

```powershell
cd backend
powershell -ExecutionPolicy Bypass -File scripts/import-media.ps1
php artisan migrate:fresh --seed
```

### Frontend (Ionic/Angular)

```bash
cd frontend
npm install
npm start                    # ng serve -> http://localhost:4200
npm run build                # genera www/ (build de produccion, con type-check de templates)
npm run lint                 # ESLint sobre src/**/*.ts y *.html
npm test                     # 199 tests (Vitest)

# Capacitor (Android/iOS)
npm run mobile:sync          # npx cap sync
npm run mobile:add           # agrega las plataformas nativas
```

### API interactiva

```bash
cd backend
php artisan serve
# Documentacion OpenAPI: http://127.0.0.1:8000/docs
# Health check:         http://127.0.0.1:8000/api/v1/health
```

---

## Requisitos del entorno

| Herramienta     | Version                    | Uso                            |
| --------------- | -------------------------- | ------------------------------ |
| Node.js         | 22.15+                     | Frontend                       |
| npm             | 10+                        | Gestor de paquetes             |
| PHP             | 8.2+                       | Backend Laravel                |
| Composer        | 2+                         | Dependencias PHP               |
| MySQL / MariaDB | 8+ / 10.4+                 | Base de datos (localhost:3306) |

---

## Configuracion del entorno

### Backend (`backend/.env`)

| Variable                        | Descripcion                                        | Default                    |
| ------------------------------- | -------------------------------------------------- | -------------------------- |
| `APP_URL`                       | URL del backend                                    | `http://127.0.0.1:8000`    |
| `APP_TIMEZONE`                  | Zona horaria (interpreta `start_at` de las reservas) | `America/Bogota`         |
| `DB_DATABASE`                   | Nombre de la BD                                    | `seven_db`                 |
| `DB_USERNAME` / `DB_PASSWORD`   | Credenciales MySQL                                 | `root` / -                 |
| `API_PREFIX`                    | Prefijo de versionado de la API                     | `v1`                       |
| `ADMIN_API_TOKEN`               | Token compartido LEGACY del backoffice (deprecado). Vacio + legacy activo, `/admin/*` responde `503` | - (fail-closed) |
| `ADMIN_LEGACY_TOKEN_ENABLED`    | Acepta el token compartido (`true`, solo `local`/`testing`) o solo Sanctum (`false`) | `false`        |
| `SECURITY_ENFORCE_PRODUCTION`   | Fail-secure: en `production` aborta `503` si `APP_DEBUG=true` o legacy activo | `true`         |
| `SANCTUM_TOKEN_EXPIRATION`      | Minutos de vida del token personal (`null` = sin expiracion)             | `43200` (30 dias)          |
| `ADMIN_LOGIN_ROLES`             | Roles que pueden iniciar sesion en el panel                             | `SUPER_ADMIN,ADMIN_SEVEN` |
| `API_SEARCH_MODE`               | `like` o `fulltext` para la busqueda `?q=`          | `like`                     |
| `API_RATE_LIMIT_GUEST`          | Peticiones/min para anonimos                       | `120`                      |
| `API_RATE_LIMIT_ADMIN`          | Peticiones/min para el backoffice                   | `60`                       |
| `MEDIA_DISK`                    | Disco de archivos multimedia                        | `public`                   |
| `MEDIA_MAX_KB`                  | Tamano maximo de subida                            | `5120`                     |
| `MEDIA_CDN_URL`                 | Prefijo CDN para `mediaUrl()`                       | -                          |
| `WHATSAPP_DEFAULT_COUNTRY`       | Prefijo telefonico por defecto                      | `57`                       |

Paginacion: 15 registros por pagina, maximo 50 por peticion.

### Frontend (`frontend/src/environments/environment*.ts`)

| Archivo                  | Uso                | URL del API                                |
| ------------------------ | ------------------ | ------------------------------------------ |
| `environment.ts`         | `npm start` (dev)  | `http://127.0.0.1:8000/api`                |
| `environment.prod.ts`    | `npm run build`    | `https://api.seven.app/api` (a confirmar)  |

---

## Autenticacion y acceso

La API publica **v1 es anonima**: el viajero reserva sin login y cada dispositivo se
identifica con un `install_id` generado localmente, de modo que los cambios de estado
llegan solo a ese telefono.

El backoffice `/api/v1/admin/*` usa **Laravel Sanctum**: cada administrador tiene
su propia cuenta, se autentica con email y password, y recibe un token personal
que autoriza ademas un **rol** (`SUPER_ADMIN` o `ADMIN_SEVEN`, sembrados por
`database/seeders/RoleSeeder.php` junto al catalogo de permisos).

```bash
# 1) Login -> token personal (solo se devuelve en esta respuesta)
curl -X POST http://127.0.0.1:8000/api/v1/admin/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@seven.test","password":"password","device_name":"Panel Web"}'

# 2) Usar el token en el resto del backoffice
curl -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8000/api/v1/admin/tours
```

| Ruta                              | Metodo         | Descripcion                              |
| --------------------------------- | -------------- | ---------------------------------------- |
| `/admin/auth/login`               | POST           | Emite el token (throttle 6/min por email) |
| `/admin/auth/logout`              | POST           | Revoca el token en uso                   |
| `/admin/auth/me`                  | GET            | Usuario + roles + permisos               |
| `/admin/auth/tokens`              | GET, POST      | Lista / crea tokens personales           |
| `/admin/auth/tokens/{id}`         | DELETE         | Revoca un token                          |
| `/admin/auth/tokens/others`       | DELETE         | Revoca todos los tokens menos el actual  |

Cada token lleva la **ability** `admin` y ademas el usuario debe tener rol
`SUPER_ADMIN`/`ADMIN_SEVEN`; los recursos CRUD exigen el **permiso** concreto
(`catalog.manage`, `providers.manage`, etc.). `SUPER_ADMIN` tiene bypass global.
Los cambios relevantes (alta/edicion/borrado, cambios de estado, aprobaciones)
se registran en `audit_logs` (nunca se guardan secretos).

- Errores: `401` credenciales o token invalidos, `403` sin rol autorizado o sin
  permiso, `422` payload invalido, `429` rate limit, `503` en modo legacy sin
  `ADMIN_API_TOKEN` o si el fail-secure detecta una configuracion insegura.
- **Transicion**: la cabecera legacy `X-Admin-Token` solo se acepta con
  `ADMIN_LEGACY_TOKEN_ENABLED=true` y entorno `local`/`testing`; **en produccion
  esta deshabilitada por defecto**. `EnsureSecureProductionConfig` aborta con
  `503` si en `production` `APP_DEBUG=true` o el legacy sigue activo, salvo que
  se ponga `SECURITY_ENFORCE_PRODUCTION=false` como escape hatch.

> El panel administrativo es una API degestion, no una UI incluida en este repositorio.

---

## API Endpoints

Base: `/api/v1`. Todas las respuestas usan el envelope `apiSuccess()`
(`{ success, message, data, errors?, meta? }`).

### Contenido publico

| Ruta                                  | Metodos       | Descripcion                          |
| ------------------------------------- | ------------- | ------------------------------------ |
| `/categories`                         | GET           | Categorias con `business_count`      |
| `/categories/{category}`              | GET           | Detalle de categoria                 |
| `/businesses`                         | GET           | Listado paginado + filtros           |
| `/businesses/{business}`              | GET           | Detalle del negocio                  |
| `/businesses/{business}/availability` | GET           | Disponibilidad para una fecha        |
| `/tours`, `/tours/{tour}`             | GET           | Catálogo de tours                    |
| `/restaurants`, `/restaurants/{restaurant}` | GET     | Restaurantes con menu de productos   |
| `/inns`, `/inns/{inn}`                | GET           | Posadas nativas con habitaciones     |
| `/bakeries`, `/bakeries/{bakery}`     | GET           | Panaderias con productos             |
| `/accommodations`, `/accommodations/{accommodation}` | GET | Alojamientos             |
| `/accommodations/{accommodation}/availability` | GET | Disponibilidad            |
| `/experiences`, `/experiences/{experience}` | GET      | Experencias                          |
| `/experiences/{experience}/availability` | GET         | Disponibilidad                       |
| `/banners`                            | GET           | Banners de home                      |
| `/promotions`                         | GET           | Promociones vigentes                 |
| `/assistant/messages`                 | POST          | Asistente turistico (chat sobre el catalogo) |
| `/health`                             | GET           | Health check                         |

Filtros de listado: `page`, `per_page` (max 50), `q`, `category` (por slug),
`featured`, y los especificos de cada recurso (`guests`, `date`…).

### Reservas

| Ruta                       | Metodos       | Descripcion                                     |
| -------------------------- | ------------- | ----------------------------------------------- |
| `/bookings`                | POST          | Crear reserva anonima                           |
| `/bookings`                | GET           | Listar por `customer_id` (paginado)             |
| `/bookings/{bookingCode}`  | GET           | Consultar por codigo publico (`SEV-XXXXXX`)     |
| `/bookings/{bookingCode}`  | PUT           | Actualizar estado                               |
| `/bookings/{bookingCode}`  | DELETE        | Cancelar                                        |

Tipos de servicio: `gastronomic` (reserva de mesa), `accommodation`, `experience`.
Objetos reservables (`bookable_type`): `business`, `accommodation`, `experience`, `tour`.

### Panel administrativo (requiere sesion)

| Recurso         | Rutas                     | Metodos                          |
| --------------- | ------------------------- | -------------------------------- |
| `categories`    | `/admin/categories`       | GET, POST, PUT/PATCH, DELETE     |
| `businesses`    | `/admin/businesses`       | GET, POST, PUT/PATCH, DELETE     |
| `tours`         | `/admin/tours`            | GET, POST, PUT/PATCH, DELETE     |
| `restaurants`   | `/admin/restaurants`      | GET, POST, PUT/PATCH, DELETE     |
| `bakeries`      | `/admin/bakeries`         | GET, POST, PUT/PATCH, DELETE     |
| `inns`          | `/admin/inns`             | GET, POST, PUT/PATCH, DELETE     |
| `accommodations`| `/admin/accommodations`   | GET, POST, PUT/PATCH, DELETE     |
| `experiences`   | `/admin/experiences`      | GET, POST, PUT/PATCH, DELETE     |
| `providers`     | `/admin/providers`        | GET, POST, PUT/PATCH, DELETE     |
| `banners`       | `/admin/banners`          | GET, POST, PUT/PATCH, DELETE     |
| `promotions`    | `/admin/promotions`       | GET, POST, PUT/PATCH, DELETE     |
| `media`         | `/admin/media`            | GET (index/show), POST, PUT/PATCH, DELETE |
| `bookings`      | `/admin/bookings`         | GET, GET por codigo, PATCH `/status` |

Tipos de proveedor: `tour_operator`, `restaurant`, `native_inn`,
`experience_provider`, `bakery`.

Notas de comportamiento del backoffice:

- Los listados aceptan `status` y `?q=` (busqueda por columnas indexadas).
- **No se puede borrar** un objeto que tenga reservas asociadas salvo que se fuerce
  con `?force=1`, para no dejar reservas apuntando a un registro inexistente.
- Los estados validos son `pending`, `approved`, `rejected` e `inactive`.

---

## Base de datos

- `categories` — verticales de negocio, seed con 6 categorías.
- `businesses` — negocios con categoría FK, geolocalización, tags, horarios y ratings.
- `accommodations`, `experiences` — catálogo clásico con `rating_avg` / `rating_count`.
- `customers` — huespedes anonimos (se deduplican por telefono/correo).
- `bookings` — reservas polimorficas (`bookable_type` / `bookable_id`),
  `booking_code` publico (`SEV-XXXXXX`), estados `pending|confirmed|cancelled|completed`.
- Marketplace: `providers`, `tours`, `restaurants`, `bakeries`, `inns` (con productos,
  habitaciones e imagenes), `banners`, `promotions`, `media` y `notifications`.

Los seeders cargan datos **reales** de San Andrés (tours de Johnny Cay y Acuario,
La Regatta, Donde Francesca, posadas nativas Miss Trinie, Atardecer Isleño y Miss Casira,
panaderías Suzette, Trigo Dorado y Susany) con fotos CC BY-SA de Wikimedia Commons.
Verificación de datos y fuentes en `backend/database/seeders/MarketplaceSeeder.php`.

---

## Estado de calidad

| Componente | Comando                     | Resultado              |
| ---------- | --------------------------- | ---------------------- |
| Backend    | `vendor\bin\phpunit`        | 156 tests, 614 aserciones |
| Backend    | `vendor\bin\pint --test`    | Sin desviaciones       |
| Frontend   | `npm test`                  | 199 tests               |
| Frontend   | `npm run lint`              | Sin errores            |
| Frontend   | `npm run build`             | Build AOT correcto     |

La suite `backend/tests/Feature/RegressionTest.php` cubre los defectos ya
corregidos: enums de estado alineados con la migracion, tipos de proveedor,
`down()` de la migracion de indices, `model_type` en media, `business_count` en
categorias, tipo `tour` en el panel de reservas, ratings persistidos, filtro `?q=`
en el backoffice y el bloqueo de borrados con reservas asociadas.

---

## Referencias

- `docs/ARCHITECTURE.md`: modelado, decisiones y estructura del backend.
- `docs/API.md`: contrato HTTP de la API v1.
- `backend/routes/api/v1/api.php`: definicion completa de rutas (99 en total).
- `backend/config/api.php`: paginacion, auth del backoffice, modo de busqueda y rate limits.
