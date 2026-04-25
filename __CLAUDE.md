# Constitución del Proyecto

> Este archivo es la fuente de verdad operativa para cualquier agente de IA (Claude Code en particular) que trabaje en este repositorio. **No se ignora, no se reinterpreta.** Si una instrucción del usuario entra en conflicto con este documento, detente y pregunta.

---

## 1. Propósito

Sistema de gestión para veterinarias **multisucursal y multitenant**. Arranca con una clínica piloto y debe escalar a 30–100 clínicas sin refactor. Modular, auditable, preparado para auditorías estrictas de medicamentos controlados (NOM/COFEPRIS), con UI premium orientada a desktop/tablet.

El contexto funcional completo (módulos, flujos, alcance) vive en `docs/brief.md`. Este archivo cubre **cómo se construye**, no qué se construye.

---

## 2. Líneas rojas (innegociables)

1. **Aislamiento por clínica es sagrado.** Ninguna query puede retornar datos de otra clínica, nunca, bajo ninguna circunstancia. Todo modelo de dominio **debe** usar el trait `BelongsToClinic` con global scope.
2. **Nada se borra físicamente.** Todos los modelos de dominio usan `SoftDeletes`. `forceDelete()` está prohibido en código de aplicación.
3. **Todo queda auditado.** Todo modelo de dominio es `Auditable`. No hay excepciones "temporales".
4. **Medicamentos controlados no se editan ni borran.** Las correcciones son contramovimientos con folio nuevo, firma, cédula y justificación.
5. **Sin amarre a proveedores externos.** WhatsApp, Google Calendar, S3, etc. se consumen SIEMPRE a través de una interface en `app/Contracts/Integrations/`. Nunca se instancia un SDK de tercero directamente en un controlador, acción, modelo o componente.
6. **Sin secretos en el repo.** Ni en `.env.example`, ni en seeders, ni en tests.
7. **Sin llamadas a APIs externas en el request del usuario.** Siempre encoladas (jobs + queue).
8. **No se introducen paquetes nuevos sin justificarlo en la task correspondiente.** Si falta un paquete, la task debe declararlo explícitamente.
9. **Preferir el comando `rg` en lugar de `grep`** Cuando rg esté disponible utilizarlo en lugar de grep para búsquedas de archivos o para comandos de consola.

---

## 3. Stack fijo

| Capa | Tecnología | Notas |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3+) | |
| Auth | Laravel Fortify | Sin Breeze ni Jetstream |
| Frontend | Vue 3 + TypeScript + Inertia 2 | Vue Starter Kit oficial |
| Routing tipado | Laravel Wayfinder | Para TS en frontend |
| CSS | Tailwind CSS + shadcn-vue | Tokens semánticos, ver §12 |
| DB | PostgreSQL 16+ | JSONB para configuraciones |
| Cache/Queue | Redis | Queue driver = redis desde día 1 |
| WebSockets | Laravel Reverb | Nada de Pusher |
| Permisos | spatie/laravel-permission | Teams feature activada |
| Auditoría | owen-it/laravel-auditing | |
| Impersonation | lab404/laravel-impersonate | Solo superadmin |
| Testing | Pest 3 | Feature + Unit |
| Code style | Laravel Pint (default preset) | |
| Static analysis | Larastan nivel 6 | |
| MCP dev | laravel/boost | Ver §13 |

**Versiones exactas** se pinean vía composer.lock / package-lock.json. No actualizar mayores sin task explícita.

---

## 4. Configuración de marca, dominio y subdominios

**IMPORTANTE:** Toda referencia a "vetfollow", "admin" u otro valor de marca/dominio debe consultarse desde `config/branding.php`, **NUNCA hardcodeado**.

Archivo centralizado: `config/branding.php`
Variables en `.env`:
- `APP_BRAND_NAME` — nombre de la plataforma
- `APP_APEX_DOMAIN` — dominio sin subdominios (ej. `mivet.com.mx`)
- `SUPERADMIN_SUBDOMAIN` — subdominio para ti (NO "admin"; algo único)

Con esto, un cambio en `.env` se refleja en TODO el sistema: URLs, cookies, validaciones, logs.

---

## 5. Estructura de carpetas

```
app/
├── Domain/                       # Un folder por módulo de negocio
│   ├── Clinic/                   # Gestión de clínicas (solo superadmin)
│   │   ├── Actions/              # Una clase = una operación de negocio
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Events/
│   │   ├── DataTransferObjects/
│   │   └── Enums/
│   ├── Catalog/                  # Catálogos base (ver §18)
│   │   ├── Geographic/           # Países, Estados, Municipios, CPs
│   │   └── Veterinary/           # Especies, Razas, Colores, Tamaños, Temperamentos
│   ├── Patient/                  # Tutores (dueños) + Pacientes (mascotas) + expediente
│   ├── Inventory/
│   ├── Appointment/
│   ├── Pos/
│   ├── Grooming/
│   ├── Hospitalization/
│   ├── Supplier/
│   ├── Notification/
│   └── Reporting/
├── Http/
│   ├── Controllers/              # Delgados. Solo orquestan.
│   │   ├── Admin/                # Superadmin ({SUPERADMIN_SUBDOMAIN}.{APEX_DOMAIN})
│   │   ├── Clinic/               # App clínica ({clinic}.{APEX_DOMAIN})
│   │   └── Portal/               # Portal cliente (fase 3)
│   ├── Middleware/
│   ├── Requests/                 # FormRequest = validación + authorize
│   └── Resources/                # Para shape hacia Inertia
├── Support/                      # Helpers cross-domain, NO lógica de negocio
├── Contracts/
│   └── Integrations/             # Interfaces de proveedores externos
├── Integrations/
│   ├── WhatsApp/Evolution/       # Impl concreta
│   ├── Calendar/Google/
│   └── Storage/Local/ | S3/
└── Providers/

resources/js/
├── pages/
│   ├── Admin/
│   ├── Clinic/
│   └── Portal/
├── components/
│   ├── ui/                       # shadcn-vue base
│   └── domain/                   # Componentes específicos por dominio
├── composables/
├── layouts/
└── types/                        # Generados por Wayfinder + manuales

.task/                            # Plan de implementación (ver §13)
docs/
├── brief.md                      # Levantamiento original del cliente
└── decisions/                    # ADRs cuando cambie algo grande
```

---

## 6. Convenciones de código

### Controladores
Delgados. **Máximo 10 líneas por método.** Solo:
1. Valida vía FormRequest inyectado
2. Ejecuta una Action
3. Devuelve Inertia::render / Resource / redirect

### Actions
- Una clase = una operación de negocio (`CreateClinicAction`, `RegisterControlledDrugEgressAction`).
- Método público único: `handle(...)`.
- Reciben DTOs o parámetros escalares, nunca FormRequests.
- Disparan eventos cuando corresponde.
- Son el único lugar donde puede ir lógica transaccional (`DB::transaction`).

### FormRequests
- Uno por operación.
- `authorize()` siempre implementado (usa Policy).
- `rules()` con Rule objects cuando aplique.
- `prepareForValidation()` para normalizar input.

### Policies
- Una por modelo.
- Registradas en `AuthServiceProvider`.
- Asumen usuario autenticado y clínica resuelta.
- Checan permisos vía `$user->can('modulo.accion')`.

### Resources / DTOs
- Hacia el frontend: API Resource (JSON para Inertia).
- Entre capas backend: DTO inmutable (readonly class PHP 8.3).

### Modelos Eloquent
- Solo relaciones, casts, scopes, accessors/mutators.
- **Nada de lógica de negocio en modelos.** Si hay un método que hace más que setear/leer estado, es una Action.

### Nombres
- Tablas: plural snake_case (`clinics`, `patient_records`).
- Modelos: singular PascalCase.
- Rutas: kebab-case (`/clinics/{clinic}/appointments`).
- Permisos: `dominio.accion` (`clinics.view`, `inventory.create`).
- Eventos: pasado indicativo (`ClinicCreated`, `DrugEgressRegistered`).

---

## 7. Multitenancy

### Resolución
- Subdominio → clínica. Middleware `ResolveClinic` lee `$request->getHost()`, extrae el subdominio, busca `clinic_by_slug`, la bindea como `app()->instance('current.clinic', $clinic)`.
- Si no existe → 404 genérico (no "clínica no encontrada", para no filtrar enumeración).
- Subdominio `admin` está reservado para superadmin.
- Subdominio `www` y el apex → landing pública.

### Aislamiento
- Trait `BelongsToClinic` sobre todo modelo de dominio:
  - Agrega `clinic_id` fillable.
  - Define relación `clinic()`.
  - Registra global scope que inyecta `where clinic_id = current_clinic()->id`.
  - En el `creating` event, setea `clinic_id = current_clinic()->id` automáticamente.
- El superadmin puede desactivar el scope temporalmente vía `Clinic::withoutTenancy(fn() => ...)`. Este helper solo funciona si `auth()->user()->is_super_admin === true`. Si no, lanza excepción.

### Migraciones
- Toda tabla de dominio **debe** tener: `clinic_id` (fk, not null, indexed), `deleted_at`, `created_at`, `updated_at`.
- Índices compuestos `(clinic_id, columna_frecuente)` en todo lo consultado.

### Spatie Permission (teams)
- Activar `teams = true` en `config/permission.php`.
- El team_id = clinic_id.
- Asignación de roles/permisos siempre en el contexto de la clínica activa.

### Test obligatorio por módulo
Cada módulo debe incluir un test que verifica que la clínica A **no** puede ver/editar/borrar recursos de la clínica B, aunque envíe el ID exacto en la URL.

---

## 8. Permisos y roles

### Roles base (seeders)
- `super_admin` — global (fuera del team), acceso total.
- `clinic_admin` — por clínica.
- `veterinarian` — por clínica.
- `groomer` — por clínica.
- `receptionist` — por clínica.
- `cashier` — por clínica.

### Permisos
- Cada módulo declara sus permisos en `app/Domain/{Modulo}/Permissions.php` como constantes.
- Cuatro permisos base por recurso: `{modulo}.view`, `.create`, `.update`, `.delete`.
- Permisos finos adicionales según módulo (ej: `inventory.adjust_stock`, `pos.apply_discount`, `controlled_drugs.dispense`).
- Seeder `ModulePermissionsSeeder` registra permisos por módulo. Se ejecuta en cada deploy (idempotente).

### Módulos activables
- Tabla `clinic_modules` (pivot `clinic_id`, `module_key`, `activated_at`, `activated_by`).
- Middleware `EnsureModuleActive` sobre rutas del módulo: si no está activo para la clínica, 403.
- Solo el superadmin activa/desactiva módulos por clínica.

---

## 9. Soft delete, auditoría y logs

### Soft delete
- Todos los modelos de dominio: trait `SoftDeletes`.
- Borrado lógico expuesto en UI como "archivar" cuando tenga sentido al usuario.
- Restaurar: ruta y permiso explícito (`.restore`).

### Auditoría (owen-it/laravel-auditing)
- Todos los modelos de dominio: implementan `Auditable`.
- Se registran created, updated, deleted, restored.
- Configuración global: captura `ip_address`, `user_agent`, `user_id`, `url`, `tags = ['clinic:'.clinic_id]`.
- Retención: indefinida. Particionar tabla `audits` cuando supere 10M filas (tarea futura).

### Logs de aplicación
- Canal `daily` por default.
- Canal adicional `controlled_drugs` (archivo separado, retención 5 años mínimo).
- Canal adicional `security` (impersonation, login, cambios de permisos, fallos de aislamiento).

---

## 10. Medicamentos controlados (NOM / COFEPRIS)

Aplicable a productos marcados como `is_controlled = true` en el inventario (fracciones I–V).

### Reglas duras
1. Cada movimiento (entrada, salida, ajuste) genera un **folio inmutable** consecutivo por clínica.
2. Toda salida exige: receta ligada, cédula profesional del médico prescriptor, firma del dispensador (usuario autenticado con password re-challenge).
3. **No se edita un movimiento.** Las correcciones se hacen con movimiento inverso, justificación obligatoria y referencia al folio original.
4. **No se borra ni lógicamente.** El flag de soft delete no aplica a movimientos controlados.
5. Kárdex con saldo corrido: cada registro guarda `balance_after` calculado y firmado (hash SHA-256 de `prev_hash + payload`). Permite detectar manipulación directa en BD.
6. Reporte mensual exportable (CSV + PDF firmado) para COFEPRIS, listo al día 1 de cada mes.

### Implementación
- Módulo `app/Domain/Inventory/ControlledDrugs/` separado del inventario regular.
- Tabla dedicada `controlled_drug_movements` (no heredar de `inventory_movements`).
- Migrations marcadas `// NOM_CRITICAL` como señal para no tocar sin revisión.

---

## 11. Integraciones externas (Adapter pattern)

### Regla
Ningún módulo de dominio conoce a su proveedor concreto. Habla contra una interface.

### Interfaces (en `app/Contracts/Integrations/`)
- `NotificationChannel` — envía un mensaje (WhatsApp / email / SMS).
- `CalendarProvider` — sincroniza eventos.
- `MediaStorage` — guarda/lee archivos (ya existe `Storage::` de Laravel, pero queremos una capa extra para versionado por clínica y políticas de retención).
- `PaymentGateway` — cobros con tarjeta (fase POS avanzado).
- `FiscalInvoicer` — timbrado SAT (fase facturación).

### Binding
- Cada clínica tiene `clinic_settings.integrations` (JSONB) que indica qué proveedor usar para qué canal.
- El `IntegrationManager` resuelve la implementación al momento del uso, basado en la clínica activa.
- Ejemplo: clínica A usa Evolution API, clínica B usa WhatsApp Cloud API. El código de notificaciones **no cambia**.

### Credenciales
- Encriptadas en `clinic_settings` (Laravel encrypted cast).
- Nunca en `.env` del servidor (eso haría global a todas las clínicas).

---

## 12. Tiempo real

- Laravel Reverb corriendo como servicio (supervisor).
- Broadcasting: canales privados `clinic.{clinic_id}.{topico}`.
- Autorización de canal en `routes/channels.php`: verifica que el usuario pertenezca a esa clínica.
- Eventos que **deben** emitirse en tiempo real (MVP):
  - `AppointmentStatusChanged` (sala de espera).
  - `PatientAdmittedToHospitalization`.
  - `ControlledDrugDispensed` (para dashboard de seguimiento).
  - `NotificationCreated` (campana del header).
- En frontend: composable `useClinicChannel()` abstrae la suscripción.

---

## 13. UI / UX

### Tokens (CSS variables)
Definidos en `resources/css/app.css`. Se respetan en todos lados; nada hardcodeado.

```css
:root {
  --background: 222 47% 6%;       /* Grafito oscuro dominante */
  --foreground: 210 40% 98%;
  --card: 222 40% 10%;
  --card-foreground: 210 40% 98%;
  --primary: 217 91% 60%;         /* Azul petróleo */
  --primary-foreground: 210 40% 98%;
  --accent: 199 89% 48%;          /* Cyan técnico */
  --muted: 222 30% 18%;
  --muted-foreground: 215 20% 65%;
  --success: 142 71% 45%;
  --warning: 38 92% 50%;
  --destructive: 0 72% 51%;
  --border: 222 30% 18%;
  --input: 222 30% 14%;
  --ring: 217 91% 60%;
  --radius: 0.625rem;             /* Redondeado discreto */
}

.light { /* tokens de tema claro */ }
```

### Principios
- **Desktop-first**, tablet compacta, móvil mínimo viable (solo lectura para auditoría).
- Botones con `rounded-lg`, icono + label siempre que haya espacio.
- Nada a más de 3 clics del dashboard.
- Reactividad real: cambios llegan por WebSocket, no por polling ni F5.
- Densidad de información alta sin sacrificar legibilidad (inspiración: Linear, Attio).
- Tablas con columna fija, búsqueda inline, filtros como chips, paginación cursor-based para listas grandes.
- Modales para acciones rápidas; páginas completas para flujos multi-paso.
- Todo formulario: validación inline, errores claros, estado de submit, success feedback.

### Estándares UI aprendidos en desarrollo (obligatorios desde task 01)

**Idioma:** Todos los textos, mensajes, etiquetas, errores y advertencias en **español latinoamericano**. Sin inglés en la UI excepto términos técnicos universales (email, dashboard).

**Ancho de formularios:** Los formularios siempre ocupan el **100% del contenedor**. No usar `max-w-*` en páginas de admin. El layout ya provee los márgenes necesarios.

**Grids responsivos (módulos/cards):**
- `grid-cols-1 md:grid-cols-2 2xl:grid-cols-4` — para grids de módulos/cards.
- A 1366px de ancho = 2 columnas. A ≥ 1536px = 4 columnas.
- Grids de campos de formulario: `grid-cols-2 xl:grid-cols-3` o `xl:grid-cols-4`.

**Botones de formularios multi-paso (wizard):** Los botones de navegación (Anterior / Siguiente / Guardar) van **en la parte superior**, con copia al fondo para formularios largos.

**Validación en wizard:** Cada paso valida sus campos antes de avanzar al siguiente. Si el backend regresa errores, el wizard salta automáticamente al primer paso con error. Se muestra toast con conteo de errores.

**Errores en acciones (toggles, botones):** Cada componente interactivo muestra su propio mensaje de error inline (no solo toast global).

**Buscador en listados:** Toda vista de listado incluye buscador pro con debounce 300ms, ícono de lupa, botón ×, y mensaje de "sin resultados" contextual. Ver §21.

**Iconos en acciones:** Todos los botones y pasos de formulario incluyen ícono (lucide-vue-next). No hay botones sin ícono excepto links de texto.

### Librería
- shadcn-vue como base.
- Iconos: lucide-vue-next.
- Animación: `@vueuse/core` + transiciones Vue nativas. Sin GSAP ni Framer.

---

## 14. Proceso de trabajo

### Task files (`.task/`)
- `implementation-XX-{modulo}.md` = plan ejecutable de un módulo o slice.
- Se atacan **en orden numérico**.
- Cada task declara: objetivo, pre-requisitos, paquetes a instalar, migraciones, rutas, componentes UI, tests obligatorios, criterios de aceptación.
- Una task no se marca como completa hasta que pasen todos los tests declarados + `vendor/bin/pint` + `vendor/bin/phpstan analyse`.

### Flujo por task
1. Leer la task completa.
2. Leer este `CLAUDE.md`.
3. Leer `docs/brief.md` si hay dudas de producto.
4. Usar Laravel Boost (MCP) para inspeccionar schema, rutas, modelos existentes antes de crear nada nuevo.
5. Implementar en orden: migraciones → modelos → actions/policies → controllers/requests → rutas → vistas Vue → tests.
6. Correr: `php artisan test && vendor/bin/pint && vendor/bin/phpstan analyse`.
7. Escribir resumen de cambios al final de la task (sección "Resultado").

### Laravel Boost (MCP)
- Instalado como `--dev`. Debe estar registrado en `.mcp.json`.
- Úsalo para: `list-routes`, `database-schema`, `tinker`, `search-docs`.
- No grepees archivos para información que Boost puede darte estructurada.

---

## 15. Comandos de verificación obligatorios

Antes de marcar cualquier task como terminada:

```bash
php artisan test --parallel
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
npm run build
npm run typecheck
```

Todos deben pasar. Sin excepciones.

---

## 16. Qué NO hacer (anti-patrones prohibidos)

- ❌ Lógica de negocio en controladores.
- ❌ Queries Eloquent en Blade / componentes Vue.
- ❌ `Model::all()` sin paginar.
- ❌ `DB::table()` cuando existe modelo.
- ❌ Llamadas HTTP a terceros en el request del usuario (siempre en job).
- ❌ `env()` fuera de archivos `config/*`.
- ❌ Mutar estado global en tests.
- ❌ Copy-paste de lógica entre módulos (extraer a `Support/` o a una Action compartida).
- ❌ Agregar un paquete sin declararlo en la task.
- ❌ Crear una migración que modifique el schema de un módulo ajeno al que se está implementando.
- ❌ Commits con secretos, tokens, cédulas reales.
- ❌ Deshabilitar el global scope de tenancy "para debuggear más rápido".

---

## 17. Superadministrador

- Vive en `{SUPERADMIN_SUBDOMAIN}.{APP_APEX_DOMAIN}` (configurable en `config/branding.php`).
- Su usuario tiene flag `is_super_admin = true` en la tabla `users`.
- Bypasea global scope de tenancy, pero **sigue siendo auditado**.
- Capacidades:
  - CRUD de clínicas.
  - Activar/desactivar módulos por clínica.
  - Impersonar a cualquier usuario (con log crítico en canal `security`).
  - Ver estadísticas globales y por clínica.
  - Gestionar roles/permisos de administradores de clínica.
- No tiene UI para editar data de dominio de una clínica (ej: no edita un expediente clínico). Para eso usa impersonation.

---

## 18. Ambiente de desarrollo

- `php artisan serve` + `npm run dev`.
- Subdominios locales: `{SUPERADMIN_SUBDOMAIN}.{APP_APEX_DOMAIN}`, `demo.{APP_APEX_DOMAIN}` vía Laravel Herd / Valet con wildcards.
- Reverb: `php artisan reverb:start`.
- Queue: `php artisan queue:work redis`.
- Seeders: crean 1 superadmin + 2 clínicas demo + usuarios por rol.

---

## 19. Catálogos (globales y por clínica)

### Clasificación

| Tipo | Ejemplos | Mutabilidad | Aislamiento |
|---|---|---|---|
| **Geográficos globales** | Países, Estados, Municipios, Códigos Postales | Solo superadmin. Se actualizan vía comando (SEPOMEX). | Global, todos los lectura. |
| **Veterinarios base** | Especies, Razas, Colores, Tamaños, Temperamentos | Superadmin edita catálogo base. Clínica puede **agregar entradas propias** (no edita ni borra las base). | Híbrido: global + extensión por clínica. |
| **Catálogos de dominio por clínica** | Productos, Servicios, Proveedores, Clientes/Tutores, Pacientes | Clínica los gestiona libremente. | Totalmente por `clinic_id`. Usan trait `BelongsToClinic`. |

**Tutores y pacientes NO son catálogos.** Son entidades de dominio del módulo `Patient`. Se gestionan con CRUD completo, tienen relaciones, historial, etc.

### Patrón "global + extensión por clínica"

Una sola tabla. Columna `clinic_id` **nullable**:
- `clinic_id = NULL` → entrada del catálogo base, visible para todas las clínicas.
- `clinic_id = X` → entrada propia de la clínica X, solo ella la ve.
- Columna `is_system = true` en filas base: bloquea edición/borrado desde UI de clínica.

### Trait `BelongsToClinicOrGlobal`

`app/Support/Tenancy/BelongsToClinicOrGlobal.php`. Similar a `BelongsToClinic`, pero el scope aplica:

```sql
WHERE clinic_id IS NULL OR clinic_id = {current_clinic_id}
```

En el evento `creating`, si no hay `clinic_id` seteado explícitamente y existe `current.clinic`, lo setea a esa clínica (la clínica está creando una entrada propia). Para que el superadmin cree entrada base, debe invocar `Model::asGlobal()->create([...])` que explícitamente setea `clinic_id = null` y requiere `is_super_admin`.

### Reglas

- Nunca hardcodear listas de especies/razas/estados en código. Siempre consultar catálogo.
- Los selectores en UI siempre usan el endpoint `/api/catalog/{type}` con búsqueda y paginación cursor-based.
- Los IDs de catálogo base **son estables** entre ambientes (seeder usa IDs fijos 1-N). Esto permite referenciar "Canino" como `Species::find(1)` con confianza.
- Catálogos geográficos: comando `php artisan catalog:sync-sepomex {--file=path.csv}` para (re)cargar. Idempotente. No bloquea lecturas.

---

## 20. Barra lateral (Sidebar) — arquitectura unificada

**Un solo componente:** `resources/js/components/AppSidebar.vue` sirve a todos los contextos. NO crear sidebars separados por dominio.

**Contexto (`context`):** El servidor computa el contexto en `HandleInertiaRequests::resolveContext()` y lo comparte como prop global:
- `'admin'` — subdominio superadmin (`radar.makadmin.test`)
- `'clinic'` — subdominio de clínica (`demo.makadmin.test`)
- `'app'` — dominio apex o cualquier otro

`AppSidebar` lee `page.props.context` y muestra el nav correspondiente:
- `admin` → Dashboard (admin) + Clínicas
- `clinic` → nav de la clínica activa (se implementará en task de clinic app)
- `app` → Dashboard

**Toggle de tema inline:** El toggle Claro / Oscuro / Sistema vive dentro del footer de `AppSidebar`. No hay página de apariencia separada necesaria para el flujo normal.

**Reglas:**
- No duplicar links entre contextos. Cada dominio tiene su propio conjunto de nav items.
- Settings (perfil, seguridad, apariencia) son accesibles desde cualquier contexto vía `NavUser`. Cuando el usuario está en el dominio admin y va a Settings, la sidebar sigue mostrando nav admin porque `context = 'admin'`.
- `AdminLayout.vue` usa `AppSidebar` (no un sidebar distinto).
- `AppSidebarLayout.vue` también usa `AppSidebar`.

---

## 21. Buscador global (patrón obligatorio en listados)

Todo módulo con listado de recursos **debe** tener buscador. Patrón estándar:

### Backend
```php
public function index(Request $request): Response
{
    $search = $request->string('search')->trim()->toString();

    $items = Model::query()
        ->when($search !== '', fn($q) => $q->where(fn($q) => $q
            ->where('campo1', 'ilike', "%{$search}%")
            ->orWhere('campo2', 'ilike', "%{$search}%")
        ))
        ->paginate(20)
        ->withQueryString();

    return Inertia::render('Modulo/Index', [
        'items' => $items,
        'filters' => ['search' => $search],
    ]);
}
```

- Usar `whereIlike` (PostgreSQL case-insensitive). No usar `where('campo', 'like', ...)` que es case-sensitive en Postgres.
- Campos típicos: nombre comercial, razón social, email, teléfono, slug/folio.
- Pasar `filters` como prop para que el frontend inicialice el campo con el valor actual.

### Frontend
```vue
<script setup>
const search = ref(props.filters.search ?? '');
let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route.url, { search: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});
</script>

<template>
    <div class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="search" placeholder="Buscar por…" class="pl-9 pr-9" />
        <button v-if="search" class="absolute right-3 top-1/2 -translate-y-1/2 ..." @click="search = ''">
            <X class="h-4 w-4" />
        </button>
    </div>
</template>
```

- Debounce 300ms.
- `preserveState: true, replace: true` para no contaminar el historial del navegador.
- Mensaje de "sin resultados" diferenciado: si hay búsqueda activa vs. si la lista está genuinamente vacía.
- Botón "Limpiar búsqueda" cuando no hay resultados y hay término activo.

---

## 22. Notas de implementación (hallazgos en desarrollo)

### Mass assignment en User
`email_verified_at` no está en `$fillable` del modelo `User`. Para actualizarlo usar asignación directa:
```php
// ❌ Silently fails (mass assignment block)
$user->update(['email_verified_at' => now()]);

// ✅ Correcto
$user->email_verified_at = now();
$user->save();
```

### RFC mexicano
El RFC puede ser de **12 caracteres** (personas morales) o **13 caracteres** (personas físicas). La regla de validación correcta:
```php
'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{2,3}$/i'],
```

### Wayfinder con Inertia forms
`useForm().post()` / `router.post()` esperan `string`, no el objeto `RouteDefinition` que devuelve Wayfinder. Siempre usar `.url`:
```ts
// ❌ router.post(clinicRoutes.store())
// ✅
router.post(clinicRoutes.store().url, data)
form.post(clinicRoutes.store().url)
```

### Búsqueda case-insensitive en PostgreSQL
Para búsquedas case-insensitive en PostgreSQL usar el operador `ilike` directamente:
```php
->where('columna', 'ilike', "%{$search}%")
->orWhere('otra', 'ilike', "%{$search}%")
```
`whereIlike()` existe pero `orWhereIlike()` no está definido en el Builder de Eloquent (Larastan lo reporta como error). Usar `orWhere(..., 'ilike', ...)` en su lugar.

---

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `wayfinder-development` — Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `inertia-vue-development` — Develops Inertia.js v3 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, useHttp, setLayoutProps, or router; working with deferred props, prefetching, optimistic updates, instant visits, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
