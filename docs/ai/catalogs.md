# Catálogos

## Clasificación

| Tipo | Ejemplos | Mutabilidad | Aislamiento |
|---|---|---|---|
| Geográficos globales | Países, Estados, Municipios, Códigos Postales | Solo superadmin. Actualización vía comando SEPOMEX. | Global, lectura para todos. |
| Veterinarios base | Especies, Razas, Colores, Tamaños, Temperamentos | Superadmin edita base. Clínica puede agregar entradas propias. | Híbrido: global + extensión por clínica. |
| Dominio por clínica | Productos, Servicios, Proveedores, Clientes/Tutores, Pacientes | Clínica los gestiona. | Total por `clinic_id`. |

Tutores y pacientes no son catálogos. Son entidades de dominio del módulo `Patient`.

## Patrón global + extensión por clínica

Usar una sola tabla con `clinic_id` nullable:

- `clinic_id = NULL` → entrada base, visible para todas las clínicas.
- `clinic_id = X` → entrada propia de clínica X.
- `is_system = true` bloquea edición/borrado desde UI de clínica.

## Trait `BelongsToClinicOrGlobal`

Archivo esperado:

```txt
app/Support/Tenancy/BelongsToClinicOrGlobal.php
```

Scope:

```sql
WHERE clinic_id IS NULL OR clinic_id = {current_clinic_id}
```

En `creating`, si no hay `clinic_id` explícito y existe `current.clinic`, asigna la clínica activa.

Para que superadmin cree entrada base, usar `Model::asGlobal()->create([...])`, que setea `clinic_id = null` y requiere `is_super_admin`.

## Reglas

- No hardcodear listas de especies, razas, estados, municipios o similares.
- Selectores UI siempre usan endpoint `/api/catalog/{type}`.
- Búsqueda y paginación cursor-based en selectores.
- IDs de catálogo base estables entre ambientes. Seeder usa IDs fijos 1-N.
- Catálogos geográficos se sincronizan con comando:

```bash
php artisan catalog:sync-sepomex {--file=path.csv}
```

El comando debe ser idempotente y no bloquear lecturas.
