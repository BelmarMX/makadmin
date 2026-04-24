# Plan de Implementación

Índice maestro de tareas. Se ejecutan **en orden numérico**. Cada archivo `implementation-XX-*.md` es autocontenido y puede ser ejecutado en una sesión de Claude Code.

## Cómo invocar una task

Al iniciar una sesión de Claude Code:

```
Lee CLAUDE.md y .task/implementation-01-clinicas.md. Ejecuta la task completa siguiendo la constitución. No avances a la siguiente task sin confirmación mía.
```

## Roadmap

| # | Task | Estado | Dependencias | Tiempo estimado |
|---|---|---|---|---|
| 00 | `implementation-00-foundation.md` — Cimientos: multitenancy, permisos, auditoría, layouts base | ✅ Completa | — | 1 día |
| 01 | `implementation-01-clinicas.md` — Módulo Clínicas (CRUD superadmin, sucursales, módulos activables) | ✅ Completa | 00 | 1 día |
| 02 | `implementation-02-catalogos-base.md` — Catálogos globales: geográficos (SEPOMEX) + veterinarios base (especies, razas, colores, tamaños, temperamentos) | 🟡 Pendiente | 01 | 1 día |
| 03 | `implementation-03-usuarios-roles.md` — Gestión de usuarios por clínica, roles, perfil | ⚪ Por redactar | 01 | 0.5 día |
| 04 | `implementation-04-tutores-pacientes.md` — Tutores (dueños) y pacientes (mascotas): CRUD, relaciones, búsqueda, vacunas | ⚪ Por redactar | 02, 03 | 1.5 días |
| 05 | `implementation-05-expediente-clinico.md` — Historial clínico, signos vitales, diagnósticos, estudios | ⚪ Por redactar | 04 | 1.5 días |
| 06 | `implementation-06-inventario-base.md` — Catálogo productos/servicios, lotes, caducidades, kárdex | ⚪ Por redactar | 03 | 2 días |
| 07 | `implementation-07-inventario-controlados.md` — Medicamentos controlados (NOM) | ⚪ Por redactar | 06 | 1.5 días |
| 08 | `implementation-08-consulta-recetario.md` — Módulo de consulta + recetario inteligente | ⚪ Por redactar | 05, 06 | 2 días |
| 09 | `implementation-09-consentimientos.md` — Consentimientos y responsivas | ⚪ Por redactar | 08 | 0.5 día |
| 10 | `implementation-10-agenda-citas.md` — Workflow de citas encadenadas | ⚪ Por redactar | 04 | 1.5 días |
| 11 | `implementation-11-integracion-google-calendar.md` | ⚪ Por redactar | 10 | 0.5 día |
| 12 | `implementation-12-pos-caja.md` — POS, caja, cortes | ⚪ Por redactar | 06 | 2 días |
| 13 | `implementation-13-cuentas-por-cobrar.md` — CXC, abonos | ⚪ Por redactar | 12 | 1 día |
| 14 | `implementation-14-combos-paquetes.md` — Paquetes dinámicos | ⚪ Por redactar | 12 | 0.5 día |
| 15 | `implementation-15-estetica.md` — Módulo estética con tiempos por raza | ⚪ Por redactar | 10 | 1 día |
| 16 | `implementation-16-hospitalizacion.md` — Tablero de hospitalización + medicación | ⚪ Por redactar | 05 | 1.5 días |
| 17 | `implementation-17-comisiones.md` — Cálculo de comisiones (esteticistas, cirujanos) | ⚪ Por redactar | 15, 16 | 0.5 día |
| 18 | `implementation-18-proveedores.md` — Directorio, comparativos, órdenes de compra | ⚪ Por redactar | 06 | 1 día |
| 19 | `implementation-19-notificaciones-whatsapp.md` — Adapter + Evolution API | ⚪ Por redactar | 00 | 1 día |
| 20 | `implementation-20-recordatorios-automaticos.md` — Jobs programados | ⚪ Por redactar | 19 | 0.5 día |
| 21 | `implementation-21-dashboard-inteligente.md` — KPIs, utilidad, segmentación | ⚪ Por redactar | 12 | 1.5 días |
| 22 | `implementation-22-dashboard-superadmin.md` — Estadísticas globales + impersonation | ⚪ Por redactar | 01 | 1 día |
| 23 | `implementation-23-portal-cliente.md` — Portal público del dueño de mascota | ⚪ Por redactar | 08 | 2 días |
| 24 | `implementation-24-modo-demo.md` — Ofuscación de datos sensibles para demos | ⚪ Por redactar | 22 | 0.5 día |

**Total estimado MVP (tasks 00–12):** ~15 días de desarrollo enfocado.
**Total estimado sistema completo:** ~30 días.

## Convenciones de estado

- ⚪ Por redactar — existe en el roadmap, aún no tiene archivo.
- 🟡 Pendiente — archivo listo, no se ha ejecutado.
- 🔵 En progreso — se está ejecutando ahora.
- ✅ Completa — tests pasan, code review ok, mergeada.
- 🔴 Bloqueada — hay un issue que impide avanzar.

## Reglas de oro

1. **No saltes orden.** Las dependencias existen.
2. **Una task = un PR** en producción. En local, un commit por fase interna.
3. **Si una task revela que `CLAUDE.md` está incompleto, se actualiza `CLAUDE.md` primero** (con ADR en `docs/decisions/`), luego se continúa la task.
4. **Cada task cierra con su sección "Resultado"** documentando qué se hizo realmente, qué se decidió diferente y por qué.
