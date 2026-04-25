# Sistema de auto-generación de tasks (04+)

> Después de completar task 03, usa esto para que Claude Code genere automáticamente las siguientes tasks (04, 05, 06...).

---

## Cómo funciona

Tienes un **"task generator prompt"** que le das a Claude Code en terminal. El prompt:
1. Le pasa el patrón de las 3 primeras tasks.
2. Le pasa el brief funcional (qué debe hacer cada módulo).
3. Le instruye cómo redactar siguiendo la constitución.
4. **Le pide que genere task 04** (o la que sigue).
5. **Tú validas** la salida aquí en chat conmigo antes de ejecutar.

---

## Prompt para Claude Code (cópialo tal cual)

```
GENERADOR DE TASKS: Redacta la siguiente task siguiendo el patrón establecido.

Lee estos archivos primero:
- CLAUDE.md (constitución)
- docs/brief.md (levantamiento funcional)
- .task/implementation-00-foundation.md (patrón estructura)
- .task/implementation-01-clinicas.md (patrón detalle)
- .task/implementation-03-usuarios-roles.md (patrón reciente)

Contexto de roadmap (del README.md):
| Número | Task | Dependencias |
|--------|------|---|
| 04 | Tutores (dueños) y pacientes (mascotas): CRUD, relaciones, búsqueda, vacunas | 02, 03 |
| 05 | Expediente clínico universal: Historial, signos vitales, diagnósticos, estudios | 04 |
| 06 | Inventario base: Catálogo productos/servicios, lotes, caducidades, kárdex | 03 |
| 07 | Medicamentos controlados (NOM): Folios, firma, reportes | 06 |
| 08 | Consulta y recetario: Registro, pruebas, recetas ligadas a inventario | 05, 06 |
| 09 | Consentimientos y responsivas: Generación automática para cirugía/anestesia | 08 |
| 10 | Agenda de citas: Workflow encadenado (estética → baño → consulta), roles, inasistencias | 04 |
| 11 | Integración Google Calendar: Sincronización de agenda del médico | 10 |
| 12 | POS y caja: Cortes por turno y sucursal, múltiples métodos de pago | 06 |

Genera la task 04 (tutores-pacientes) con esta estructura EXACTA:

1. **Cabecera y alcance**
   - Título y objetivo en 1-2 líneas
   - Prerrequisitos (tasks anteriores)
   - Tiempo estimado
   - Referencia a secciones de CLAUDE.md

2. **Alcance**
   - Qué sí se hace (dentro)
   - Qué no se hace (fuera)

3. **Dominio** 
   - Estructura de carpetas `app/Domain/Patient/`
   - Modelos, Actions, Policies, Events, Enums

4. **Migraciones**
   - Tablas: `clients` (tutores), `patients` (mascotas), relación
   - Columnas necesarias (clinic_id, soft deletes, auditable)
   - Índices para búsqueda

5. **Modelos**
   - Client (tutor): nombre, teléfono, email, dirección, CURP, RFC
   - Patient (mascota): nombre, species_id, breed_id, birth_date, microchip, color, size, temperament
   - Relación: Client hasMany Patients

6. **Acciones (Actions)**
   - CreateClientAction
   - UpdateClientAction
   - CreatePatientAction
   - UpdatePatientAction
   - LinkPatientToClientAction

7. **Permisos**
   - clients.view, clients.create, clients.update, clients.deactivate, clients.restore
   - patients.view, patients.create, patients.update, patients.deactivate
   - Por clínica (team-scoped)

8. **FormRequests**
   - StoreClientRequest (validaciones de email único, teléfono, etc.)
   - UpdateClientRequest
   - StorePatientRequest (validaciones de catalog lookups)

9. **Controllers**
   - ClientController (CRUD)
   - PatientController (CRUD por cliente)
   - PatientSearchController (búsqueda global por microchip, nombre)

10. **Rutas**
    - Rutas RESTful con prefijo `/clients`, `/patients`
    - Búsqueda como endpoint API

11. **Frontend**
    - Páginas: ListadoClientes, CrearCliente, DetalleCliente (con tab de mascotas)
    - Páginas: ListadoMascotas, CrearMascota, DetalleMascota
    - Búsqueda rápida de cliente por email/teléfono/nombre
    - Búsqueda de mascota por microchip

12. **Tests**
    - Validaciones (email único, teléfono, etc.)
    - Relación cliente-mascota
    - Tenancy (cliente A no ve clientes B)
    - Búsqueda por microchip

13. **Criterios de aceptación** (checklist)
    - Crear cliente, asignarle 3 mascotas
    - Editar datos de cliente
    - Búsqueda de cliente por email
    - Búsqueda de mascota por microchip
    - Validaciones de campos
    - Tests pasan
    - Linting pasa

14. **Sección Resultado** (a llenar después)

Usa esta estructura EXACTA para mantener consistencia.

Guía de redacción:
- Referencias dinámicas: config('branding.*'), NO hardcodes
- Valida contra global scope (BelongsToClinic)
- Soft deletes obligatorio
- Auditable en modelos de dominio
- Permisos scoped por clinic (teams)
- Ejemplo de código: real, ejecutable, sin pseudocódigo
- Notas: en comentarios de código, marcar NOM/regulaciones si aplica

Redacta la task ahora en formato markdown. Salida: `.task/implementation-04-tutores-pacientes.md`
```

---

## Cómo usarlo

### Paso 1: Prepara el prompt

Copia el prompt anterior a un archivo temporal o mantenlo en tu clipboard.

### Paso 2: Dale a Claude Code en tu terminal

```bash
cd tu-proyecto
claude code
```

Dentro de Claude Code, pega:

```
[pega el prompt completo arriba]
```

Claude Code leerá tus archivos, seguirá el patrón y generará task 04.

### Paso 3: Copia la salida y valídala conmigo

Claude Code te entregará el contenido de `implementation-04-tutores-pacientes.md`.

**Cópialo completamente** y pégalo aquí en chat (en un mensaje) con:

```
He ejecutado el generador de tasks 04. Aquí está el resultado:

[pega todo el contenido de la task 04 generada]

¿Validado para ejecutar?
```

### Paso 4: Yo valido en chat

Reviso:
- ¿Sigue el patrón de las anteriores?
- ¿Las dependencias son correctas?
- ¿Las validaciones del dominio están bien?
- ¿Referencias dinámicas (config branding) en lugar de hardcodes?

Te digo **"✅ Validado"** o **"⚠️ Ajusta X punto"**.

### Paso 5: Guardar e ejecutar

```bash
# Guarda la task en tu repo
cat << 'EOF' > .task/implementation-04-tutores-pacientes.md
[contenido de la task]
EOF

# Ejecuta
claude code
# Lee CLAUDE.md y .task/implementation-04-tutores-pacientes.md. Ejecuta task 04...
```

---

## Para las siguientes tasks (05, 06, 07...)

Adapta el prompt anterior pero cambia:

```
Genera la task 05 (expediente-clinico) con esta estructura EXACTA:
| 05 | Expediente clínico: Historial, signos vitales, diagnósticos | 04 |

Depende de: Patients (task 04) ya existe.
Crea: app/Domain/MedicalRecord/
Modelos: Consultation, MedicalHistory, VitalSigns, Diagnosis
...
```

---

## Validación rápida (checklist)

Antes de darme la task generada, verifica en Claude Code:

```bash
# Busca hardcodes
grep -n "vetfollow\|'admin'\|\.com" .task/implementation-04-tutores-pacientes.md

# Debe retornar: 0 matches (o solo en explicaciones textuales)

# Busca referencias a config branding
grep -n "config('branding" .task/implementation-04-tutores-pacientes.md

# Debe retornar: varias coincidencias si hay URLs dinámicas
```

---

## Ventajas del sistema

✅ Automatización: ahorro de tiempo redactando  
✅ Consistencia: todas las tasks siguen el patrón  
✅ Control: tú validas antes de ejecutar  
✅ Flexibilidad: puedes pedir ajustes post-generación  
✅ Escalabilidad: de 04 a 24 sin intervención manual

---

## Próximos pasos

1. **Ejecuta task 03** en tu proyecto.
2. **Cuando termine 03**, usa el generador para task 04.
3. **Pásame task 04** aquí en chat para validar.
4. **Ejecuta 04** en el proyecto.
5. **Repite** para 05, 06... hasta completar MVP.

¿Listo para ejecutar task 03 y luego generar 04?
