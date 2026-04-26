# CLAUDE.md — Constitución operativa

Este archivo es el contexto base para agentes de IA en este repositorio. Define reglas operativas, límites técnicos y flujo de trabajo.

Si una instrucción del usuario entra en conflicto con estas reglas, detente y solicita aclaración antes de modificar código.

## Referencias bajo demanda

Carga estos documentos solo cuando la tarea toque ese tema:

- `docs/ai/architecture.md` — estructura del proyecto, convenciones backend, branding, superadmin.
- `docs/ai/multitenancy.md` — resolución de clínica, scopes, permisos por team, pruebas de aislamiento.
- `docs/ai/compliance-controlled-drugs.md` — medicamentos controlados, auditoría, kárdex, COFEPRIS.
- `docs/ai/frontend-ui.md` — UI/UX, sidebar, buscadores, patrones Vue/Inertia/Tailwind.
- `docs/ai/catalogs.md` — catálogos globales, por clínica e híbridos.
- `docs/ai/workflow.md` — proceso por task, comandos de verificación, Laravel Boost, Codex.
- `docs/ai/implementation-notes.md` — hallazgos técnicos ya aprendidos.
- `docs/brief.md` — contexto funcional del producto.

## Propósito

Sistema de gestión para veterinarias multisucursal y multitenant. Debe iniciar con una clínica piloto y escalar a 30–100 clínicas sin refactor estructural.

El sistema debe ser modular, auditable, preparado para auditorías estrictas de medicamentos controlados (NOM/COFEPRIS) y con UI premium orientada a desktop/tablet.

## Stack fijo

- Backend: Laravel 13, PHP 8.3+
- Auth: Laravel Fortify, sin Breeze ni Jetstream
- Frontend: Vue 3 + TypeScript + Inertia 2/3 según lockfile
- Routing tipado: Laravel Wayfinder
- CSS/UI: Tailwind CSS + primevue + lucide-vue-next y shadcn-vue (fallback)
- DB: PostgreSQL 16+, JSONB para configuraciones
- Cache/Queue: Redis desde día 1
- WebSockets: Laravel Reverb, no Pusher
- Permisos: spatie/laravel-permission con teams activado
- Auditoría: owen-it/laravel-auditing
- Impersonation: lab404/laravel-impersonate, solo superadmin
- Testing: Pest
- Code style: Laravel Pint
- Static analysis: Larastan nivel 6
- MCP dev: Laravel Boost

Versiones exactas se respetan desde `composer.lock` y `package-lock.json`. No actualizar mayores sin task explícita.

## Reglas críticas

1. Aislamiento por clínica obligatorio. Todo modelo de dominio debe usar `BelongsToClinic` con global scope, salvo catálogos híbridos definidos en `docs/ai/catalogs.md`.
2. Ninguna query puede filtrar, mostrar, editar o borrar datos de otra clínica.
3. Todos los modelos de dominio usan `SoftDeletes`; `forceDelete()` no debe usarse en código de aplicación.
4. Todo modelo de dominio debe ser auditable.
5. Medicamentos controlados no se editan ni borran. Las correcciones son contramovimientos con folio nuevo, firma, cédula y justificación.
6. Integraciones externas siempre pasan por interfaces en `app/Contracts/Integrations/`.
7. No instanciar SDKs externos en controllers, actions, modelos o componentes.
8. No hacer llamadas a APIs externas durante el request del usuario; usar jobs/queue.
9. No guardar secretos en el repo: ni `.env.example`, seeders, tests o documentación.
10. No agregar paquetes sin declararlo y justificarlo en la task correspondiente.
11. Preferir `rg` sobre `grep` cuando esté disponible.

## Arquitectura base

- Controladores delgados: validar, ejecutar Action, responder.
- Actions: una clase por operación de negocio, método público `handle(...)`, lógica transaccional y eventos.
- FormRequests: validación y autorización por operación.
- Policies: autorización por modelo usando permisos del contexto activo.
- Modelos: relaciones, casts, scopes y accessors/mutators; sin lógica de negocio.
- DTOs readonly para pasar datos entre capas backend.
- API Resources para shape hacia Inertia/frontend.

## Multitenancy mínimo obligatorio

- Subdominio → clínica mediante middleware `ResolveClinic`.
- `clinic_id` obligatorio en tablas de dominio.
- Índices compuestos `(clinic_id, columna_frecuente)` en consultas comunes.
- Spatie Permission usa teams; `team_id = clinic_id`.
- Cada módulo debe probar que la clínica A no puede ver/editar/borrar recursos de la clínica B, aunque conozca el ID.
- Cross-clinic access: middleware `EnsureClinicAccess` después de `auth` en rutas de clínica. Verifica `$user->clinic_id === current_clinic()->id`, exime superadmins. Middleware order: `['tenant', 'auth', 'clinic-access']`.

Detalles completos: `docs/ai/multitenancy.md`.

## Medicamentos controlados

Aplicable a productos `is_controlled = true`.

- Cada movimiento genera folio inmutable consecutivo por clínica.
- Toda salida exige receta ligada, cédula profesional, firma del dispensador y revalidación de password.
- Correcciones solo con movimiento inverso, justificación y referencia al folio original.
- Kárdex con saldo corrido y hash SHA-256 encadenado.
- Reporte mensual CSV + PDF firmado para COFEPRIS.
- Implementación separada en `app/Domain/Inventory/ControlledDrugs/`.
- Migraciones críticas marcadas con `// NOM_CRITICAL`.

Detalles completos: `docs/ai/compliance-controlled-drugs.md`.

## Integraciones externas

El dominio nunca conoce proveedores concretos. Usar interfaces:

- `NotificationChannel`
- `CalendarProvider`
- `MediaStorage`
- `PaymentGateway`
- `FiscalInvoicer`

Credenciales por clínica en `clinic_settings` con cast encriptado. No en `.env` global.

## Branding y dominios

Toda referencia a marca, dominio, subdominio admin o similares debe salir de `config/branding.php`.

Variables esperadas:

- `APP_BRAND_NAME`
- `APP_APEX_DOMAIN`
- `SUPERADMIN_SUBDOMAIN`

No hardcodear `vetfollow`, `admin` u otros valores equivalentes.

## UI mínima obligatoria

- Español latinoamericano en toda la UI.
- Desktop-first, tablet compacta, móvil mínimo viable.
- shadcn-vue como base.
- Iconos con lucide-vue-next.
- Respetar tokens CSS del proyecto.
- Listados con buscador debounce 300 ms y estados vacíos claros.

Detalles completos: `docs/ai/frontend-ui.md`.

## Flujo de trabajo

1. Leer la task completa en `.task/`.
2. Leer este archivo.
3. Cargar docs bajo demanda según el dominio tocado.
4. Usar Laravel Boost para inspeccionar schema, rutas y documentación antes de crear o modificar código.
5. Implementar en orden: migraciones → modelos → actions/policies → controllers/requests → rutas → vistas Vue → tests.
6. Validar con los comandos obligatorios definidos en `docs/ai/workflow.md`.
7. Escribir resumen de cambios en la task, sección `Resultado`.

## Delegación a Codex

Cuando esté disponible `/codex:setup`, delegar implementación local de código a Codex como task local.

- Modelo solicitado: GPT-5.4 — medium.
- Claude conserva rol de arquitectura, análisis, revisión y validación.
- Codex ejecuta cambios de código, pruebas y búsquedas locales cuando sea más eficiente.
- No duplicar trabajo: si Codex ya implementó, Claude revisa y corrige criterios, no reescribe todo desde cero.

## Verificación obligatoria antes de cerrar task

```bash
php artisan test --parallel
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
npm run build
npm run typecheck
```

Sin excepciones para marcar una task como terminada.

## Anti-patrones prohibidos

- Lógica de negocio en controladores.
- Lógica de negocio en modelos.
- Queries Eloquent dentro de Blade/componentes Vue.
- `Model::all()` sin paginar.
- `DB::table()` cuando existe modelo.
- Llamadas HTTP externas en requests.
- `env()` fuera de `config/*`.
- Mutar estado global en tests.
- Copy-paste de lógica entre módulos.
- Paquetes nuevos sin task explícita.
- Migraciones que modifiquen módulos ajenos a la task.
- Commits con secretos, tokens o datos reales sensibles.
- Desactivar global scopes de tenancy para debug.
- Nunca ejecutar `php artisan migrate:fresh` ni `php artisan migrate:refresh`.

## Componentes UI (PrimeVue preferido)

- Inputs de texto: `InputText` + `FloatLabel` variant="on".
- Selects / dropdowns: `Select` + `FloatLabel` (uno por filtro, no `<select>` nativo).
- Badges / etiquetas de roles y estado: `Chip` de PrimeVue con icono si aplica.
- Checkboxes: `Checkbox` de PrimeVue en modo `binary`.
- Botones con efecto de clic: agregar directiva `v-ripple` a acciones importantes (guardar, activar, desactivar, eliminar) mediante `app.directive('ripple', Ripple)`. NO usar `ripple: true` en global config de PrimeVue (rompe FloatLabel+Select).
- Tooltips en botones icon-only: usar `v-tooltip` (registrado globalmente via `app.directive('tooltip', Tooltip)`). Usar modificadores de posición para evitar overflow: `v-tooltip.bottom` en toolbar/header, `v-tooltip.left` en botones del lado derecho. NO usar `title` nativo en botones icon-only.
- Toast: usar PrimeVue Toast (`ToastService` plugin + `<Toast position="top-right" />` en layouts). Helper en `@/lib/toast.ts` con API `toast.success()`, `toast.error()`, `toast.info()`, `toast.warning()`. NO usar vue-sonner.
- Font-size: app usa `html { font-size: 16px }`. PrimeVue componentes se escalan con `.p-component { font-size: 0.875rem }` para equivaler a 14px. No cambiar el html font-size.
- FloatLabel "Todos" options: NO usar `{ id: null, name: 'Todos' }`. Usar sentinelas truthy: `{ id: 0, name: 'Todos' }` para números, `{ value: '__all__', label: 'Todos' }` para strings. Así FloatLabel detecta el estado "filled" y flota la etiqueta.
- FloatLabel CSS: usar `[data-pc-name="floatlabel"] { height: fit-content !important }` para override de estilos inyectados por PrimeVue en runtime.
- PermissionGrid: NO poner `@click` handlers en el `<td>` del checkbox (causa doble-toggle). `toggleModule` en la primera `<td>` (nombre del módulo), no en el `<tr>`. Sincronizar `form.data.branch_id` y `form.data.permissions` via `watch` sobre `props.branchId` y `props.permissions`.
- Status badges: `UserStatusBadge` usa `bg-success text-white` para activo, `variant="destructive"` para inactivo.
- SSR-safe: usar `composables/useClinicSlug.ts` con guard `typeof window` en lugar de `window.location.hostname` directo.
- Dashboard / listados por defecto: agregar iconos a tabs con lucide, iconos a datos generales (Mail, Phone, Building2, etc.).
- Wayfinder routes: regeneradas automáticamente por `@laravel/vite-plugin-wayfinder` en cada build. NO committed (.gitignore). Si cambia `APP_APEX_DOMAIN`, reiniciar dev o rebuild.
- Shadcn Button / Card / Avatar / Badge se mantienen para estructura de layout.
- No mezclar `<select>` nativo con PrimeVue en la misma vista.
- UI siempre en español latinoamericano. Nunca mostrar claves internas (ej. `clinic_admin`, `veterinarian`) directamente al usuario; usar `roleLabel()` de `@/lib/userLabels`.

## Configuración por defecto (Roles & Módulos)

- `config/role-module-defaults.php` define qué módulos están activos por defecto para cada rol.
- Se usa al crear una clínica nueva y al restaurar defaults via `ClinicRoleModuleController::restore()`.
- Ruta: `POST /clinics/{clinic}/role-modules/restore` (alias `clinic-access`).
