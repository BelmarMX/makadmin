# Frontend, UI y UX

## Principios UI

- Desktop-first.
- Tablet compacta.
- Móvil mínimo viable, principalmente lectura para auditoría.
- Nada a más de 3 clics del dashboard.
- Alta densidad de información sin sacrificar legibilidad.
- Inspiración visual: Linear, Attio.
- UI en español latinoamericano. Evitar inglés salvo términos técnicos universales como email o dashboard.

## Tokens CSS

Los tokens viven en `resources/css/app.css` y deben respetarse en todos los componentes.

```css
:root {
  --background: 222 47% 6%;
  --foreground: 210 40% 98%;
  --card: 222 40% 10%;
  --card-foreground: 210 40% 98%;
  --primary: 217 91% 60%;
  --primary-foreground: 210 40% 98%;
  --accent: 199 89% 48%;
  --muted: 222 30% 18%;
  --muted-foreground: 215 20% 65%;
  --success: 142 71% 45%;
  --warning: 38 92% 50%;
  --destructive: 0 72% 51%;
  --border: 222 30% 18%;
  --input: 222 30% 14%;
  --ring: 217 91% 60%;
  --radius: 0.625rem;
}

.light {
  /* tokens de tema claro */
}
```

No hardcodear colores si existe token semántico.

## Componentes y librerías

- primevue como base.
- shadcn-vue como fallback.
- Iconos: lucide-vue-next.
- Fechas y calendarios: usar `DatePicker` de PrimeVue. No usar `input[type="date"]` ni componentes nativos para selección de fechas.
- Inputs de captura: preferir `IconField` + `InputIcon` con `InputText` en formularios de datos cuando el icono ayude al reconocimiento del campo.
- Animación: `@vueuse/core` y transiciones Vue nativas.
- Toast: PrimeVue Toast con `ToastService` + `<Toast position="top-right" />` en layouts. Helper `@/lib/toast.ts` expone `toast.success()`, `toast.error()`, `toast.info()`, `toast.warning()`. No usar vue-sonner.
- Font-size PrimeVue: html base 16px. Componentes PrimeVue escalan a 14px via `.p-component { font-size: 0.875rem }`. No cambiar html font-size.
- FloatLabel sentinelas: para opciones "Todos" en filters NO usar `null` (rompe estado filled). Usar `{ id: 0, name: 'Todos' }` para números, `{ value: '__all__', label: 'Todos' }` para strings.
- FloatLabel CSS override: PrimeVue inyecta estilos en runtime. Usar `[data-pc-name="floatlabel"] { height: fit-content !important }` para forzar altura automática.
- No usar GSAP ni Framer.

## Formularios

- Formularios al 100% del contenedor.
- No usar `max-w-*` en páginas de admin.
- Grids de campos: `grid-cols-2 xl:grid-cols-3` o `xl:grid-cols-4`.
- Validación inline.
- Errores claros.
- Estado de submit visible.
- Success feedback.

## Wizards

- Botones Anterior/Siguiente/Guardar en la parte superior.
- Copia al fondo si el formulario es largo.
- Cada paso valida antes de avanzar.
- Si backend regresa errores, saltar automáticamente al primer paso con error.
- Toast con conteo de errores.

## Botones e iconos

- Botones con `rounded-lg`.
- Icono + label siempre que haya espacio.
- No botones sin icono excepto links de texto.

## Grids responsivos

Para módulos/cards:

```txt
grid-cols-1 md:grid-cols-2 2xl:grid-cols-4
```

A 1366px: 2 columnas. A ≥1536px: 4 columnas.

## Tablas y listados

- Columna fija cuando aplique.
- Búsqueda inline.
- Filtros como chips.
- Paginación cursor-based para listas grandes.
- Estados vacíos diferenciados.

## PermissionGrid

- `toggleModule` va en la primera `<td>` (nombre del módulo), no en el `<tr>`.
- El `<td>` del checkbox NO lleva `@click`. Solo el checkbox maneja su toggle.
- Sincronizar `form.data.branch_id` via `watch` de `props.branchId` (el form se crea una vez en mount, no se actualiza solo).
- Las URLs de acción pasan `?branch_id=` como query param; el form también envía `branch_id` en el body.

## Buscador obligatorio en listados

Todo listado de recursos debe tener buscador.

### Backend

```php
public function index(Request $request): Response
{
    $search = $request->string('search')->trim()->toString();

    $items = Model::query()
        ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
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

- PostgreSQL: usar `ilike`.
- No usar `like` para búsqueda case-insensitive en Postgres.
- `orWhereIlike()` no está definido en Eloquent Builder; usar `orWhere('campo', 'ilike', ...)`.
- Pasar `filters` para inicializar el frontend.

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
        <button v-if="search" class="absolute right-3 top-1/2 -translate-y-1/2" @click="search = ''">
            <X class="h-4 w-4" />
        </button>
    </div>
</template>
```

- Debounce 300ms.
- `preserveState: true`.
- `replace: true` para no contaminar historial.
- Mensaje “sin resultados” contextual.
- Botón “Limpiar búsqueda” si hay término activo sin resultados.

## Sidebar

Un solo componente: `resources/js/components/AppSidebar.vue`.

No crear sidebars separados por dominio.

### Contexto

El servidor calcula contexto en `HandleInertiaRequests::resolveContext()` y lo comparte como prop global:

- `admin` — subdominio superadmin.
- `clinic` — subdominio de clínica.
- `app` — apex u otro contexto.

`AppSidebar` lee `page.props.context`:

- `admin` → Dashboard admin + Clínicas.
- `clinic` → navegación de clínica activa.
- `app` → Dashboard.

### Tema

Toggle Claro/Oscuro/Sistema vive dentro del footer de `AppSidebar`.

No crear página de apariencia para el flujo normal.

### Reglas

- No duplicar links entre contextos.
- Settings son accesibles desde cualquier contexto vía `NavUser`.
- `AdminLayout.vue` usa `AppSidebar`.
- `AppSidebarLayout.vue` usa `AppSidebar`.

## Tiempo real

- Laravel Reverb con supervisor.
- Broadcasting en canales privados `clinic.{clinic_id}.{topico}`.
- `routes/channels.php` valida pertenencia del usuario a la clínica.

Eventos MVP en tiempo real:

- `AppointmentStatusChanged`
- `PatientAdmittedToHospitalization`
- `ControlledDrugDispensed`
- `NotificationCreated`

Frontend: composable `useClinicChannel()` abstrae la suscripción.
