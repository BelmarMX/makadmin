# Flujo de trabajo para agentes

## Task files

- `.task/implementation-XX-{modulo}.md` define plan ejecutable.
- Atacar tasks en orden numérico.
- Cada task debe declarar:
  - objetivo
  - prerequisitos
  - paquetes a instalar
  - migraciones
  - rutas
  - componentes UI
  - tests obligatorios
  - criterios de aceptación
- Una task no está completa hasta que pasen tests + Pint + PHPStan + build + typecheck.
- Al finalizar, escribir resumen en sección `Resultado`.

## Flujo por task

1. Leer task completa.
2. Leer `CLAUDE.md`.
3. Leer `docs/brief.md` si hay dudas de producto.
4. Cargar docs de `docs/ai/*` según el área tocada.
5. Usar Laravel Boost para inspeccionar schema, rutas, modelos y documentación antes de crear código.
6. Implementar en orden:
   - migraciones
   - modelos
   - actions/policies
   - controllers/requests
   - rutas
   - vistas Vue
   - tests
7. Correr validaciones.
8. Actualizar `Resultado` en la task.

## Laravel Boost MCP

Debe estar instalado como dev y registrado en `.mcp.json`.

Usar para:

- `list-routes`
- `database-schema`
- `database-query` read-only
- `tinker` cuando sea necesario
- `search-docs`
- `browser-logs`
- `get-absolute-url`

Preferir Boost sobre inspección manual cuando dé información estructurada.

## Documentación versionada

Antes de cambios de código, usar `search-docs` con queries amplias y paquetes relevantes cuando aplique.

Ejemplos:

- `['authorization policies', 'form request authorization']`
- `['inertia form validation', 'router visit']`
- `['broadcast private channels', 'reverb']`

No meter nombres de paquetes en la query si ya se pasan como paquete.

## Comandos obligatorios al cerrar task

```bash
php artisan test --parallel
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
npm run build
npm run typecheck
```

## Verificación incremental

Durante desarrollo, correr pruebas mínimas relevantes:

```bash
php artisan test --compact --filter=NombreDelTest
php artisan test --compact tests/Feature/ModuloTest.php
vendor/bin/pint --dirty --format agent
```

## Reglas Laravel

- Usar `php artisan make:*` para crear clases Laravel.
- Pasar `--no-interaction` en comandos Artisan cuando aplique.
- No crear scripts de verificación si tests cubren la funcionalidad.
- No borrar tests sin aprobación.
- Si se modifica PHP, correr Pint.

## Codex local

Cuando `/codex:setup` esté disponible:

- Delegar implementación local de código a Codex.
- Modelo: GPT-5.4 — medium.
- Claude mantiene arquitectura, revisión y criterios.
- Codex puede ejecutar búsqueda local, edición y pruebas.
- Claude debe validar que la implementación respete `CLAUDE.md` y docs relacionadas.
