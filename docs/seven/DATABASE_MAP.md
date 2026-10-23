# SEVEN — Mapa de base de datos

Motor: MySQL/MariaDB. BD local: `seven_db`.
Fuente: `backend/database/migrations/` (solo lectura en esta fase).

## Tablas (40)
### Infraestructura Laravel
| Tabla | Propósito |
|-------|-----------|
| `users` | Usuarios del sistema (admin, proveedor) |
| `password_reset_tokens` | Recuperación de contraseña |
| `sessions` | Sesiones |
| `cache`, `cache_locks` | Caché |
| `jobs`, `job_batches`, `failed_jobs` | Colas |
| `personal_access_tokens` | Tokens Sanctum |

### Acceso y auditoría
| Tabla | Columnas clave |
|-------|----------------|
| `roles` | `name`, `label` |
| `permissions` | `name`, `label` |
| `role_user` | `role_id`, `user_id` (PK compuesta) |
| `permission_role` | `permission_id`, `role_id` (PK compuesta) |
| `audit_logs` | `user_id`, `action`, `auditable_type/id`, `metadata` |

### Catálogo clásico
| Tabla | Columnas clave |
|-------|----------------|
| `categories` | `slug`, `name`, `icon`, `image`, `sort_order`, `is_active` |
| `businesses` | `category_id`, `name`, `slug`, `address`, `lat/long`, `images`, `opening_hours`, `tags`, `features`, `is_verified`, `status` |
| `accommodations` | `name`, `slug`, `capacity`, `price_per_night`, `currency`, `lat/long`, `images`, `included_services`, `user_id`, `status` |
| `experiences` | `name`, `slug`, `category_id`, `duration_minutes`, `price_per_person`, `max_capacity`, `includes`, `images`, `user_id`, `status` |

### Reservas
| Tabla | Columnas clave |
|-------|----------------|
| `customers` | `name`, `phone`, `email`, `guest_token` |
| `bookings` | `customer_id`, `booking_code`, `bookable_type/id`, `type`, `start_at`, `end_at`, `guests`, `total_amount`, `currency`, `status` |
| `booking_status_histories` | `booking_id`, `from_status`, `to_status`, `changed_by`, `note` |

Relación: `customers 1—N bookings`; `bookings` es polimórfica hacia
`Business`, `Accommodation` o `Experience`.

### Marketplace
| Tabla | Relación |
|-------|----------|
| `providers` | Tenant raíz; `user_id`, `business_name`, `type`, `status`, `verification_status` |
| `tours` | `provider_id`; `slug`, `price`, `rating`, `cover_image`, `status` |
| `tour_images` | `tour_id`, `image`, `order` |
| `tour_includes` | `tour_id`, `icon`, `name` |
| `tour_recommendations` | `tour_id`, `icon`, `name` |
| `restaurants` | `provider_id`; `slug`, `category`, `rating`, `opening_hours`, `lat/long`, `status` |
| `restaurant_products` | `restaurant_id`, `price`, `category`, `status` |
| `inns` | `provider_id`; `slug`, `price`, `services`, `availability`, `rating`, `status` |
| `inn_rooms` | `inn_id`, `price`, `capacity` |
| `bakeries` | `provider_id`; `slug`, `category`, `rating`, `status` |
| `bakery_products` | `bakery_id`, `price`, `category`, `status` |
| `promotions` | `provider_id`, `discount`, `price`, `start_date`, `end_date`, `status` |
| `news` | `provider_id` opcional; `slug`, `kind`, `published_at`, `is_featured`, `status` |
| `banners` | `title`, `image`, `button_url`, `start_date`, `end_date`, `status`, `order` |

### Contenido y engagement
| Tabla | Columnas clave |
|-------|----------------|
| `media` | `model_type/id`, `file_path`, `type`, `order` (polimórfica) |
| `comments` | `commentable_type/id`, `install_id`, `author_name`, `rating`, `body`, `status` |
| `notifications` | `provider_id`, `title`, `message`, `type`, `read` |

### Asistente
| Tabla | Columnas clave |
|-------|----------------|
| `assistant_conversations` | `id` char(26), `install_id`, `expires_at`, `last_activity_at` |
| `assistant_messages` | `conversation_id`, `role`, `content`, `payload` |

## Diagrama resumido
```
categories ──< businesses
           └─< experiences
customers ──< bookings >── (bookable) businesses | accommodations | experiences
bookings ──< booking_status_histories

providers ──< tours ──< tour_images | tour_includes | tour_recommendations
          ├─< restaurants ──< restaurant_products
          ├─< inns ──< inn_rooms
          ├─< bakeries ──< bakery_products
          ├─< promotions
          └─< news

media (model_type/model_id) ──> tours | restaurants | inns | banners | …
comments (commentable_type/id) ──> tours | restaurants | inns
users ──< personal_access_tokens
users >──< roles >──< permissions
assistant_conversations ──< assistant_messages
```

## Índices
- `bookings(status, start_at)`
- `businesses(status, is_verified)`
- `accommodations(status, capacity)`
- `experiences(status, max_capacity)`
- `providers(type)`, `providers(status)`
- `tours/restaurants/inns/bakeries/promotions(provider_id, status)`
- `comments` único `(commentable_type, commentable_id, install_id)` y
  `(commentable_type, commentable_id, status, created_at)`
- `news(status, published_at)`, `news(is_featured, order)`
- `media(model_type, model_id, type, order)`
- `audit_logs(auditable_type, auditable_id)`, `(user_id, created_at)`, `(action)`

## Reglas
- No modificar esquema en Fase 0.
- Dinero en `unsignedBigInteger` (COP), no float.
- Coordenadas `decimal(10,7)` o `string(20)` según tabla existente.
- Toda FK nueva con índice y `onDelete` explícito.
- No ejecutar `migrate:fresh` sobre datos reales.
