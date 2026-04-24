# Para Claude Code: Respeto a la arquitectura de parametrización

> **Lee esto antes de cualquier task.** Si la encuentras violada, restaura primero.

## Línea roja: Parametrización no se negocia

Este proyecto tiene una decisión arquitectónica inmutable (ver ADR-001):

**Toda referencia a marca, dominio o subdominio debe venir de `config/branding.php`.**

```php
// ❌ NUNCA ESTO
'Vive en admin.vetfollow.com'
if ($subdomain === 'admin') { ... }
$url = "https://vetfollow.com";
config('app.apex_domain')  // ← usa branding, no app

// ✅ SIEMPRE ESTO
'Vive en ' . config('branding.superadmin_subdomain') . '.' . config('branding.apex_domain')
if ($subdomain === config('branding.superadmin_subdomain')) { ... }
$url = 'https://' . config('branding.superadmin_subdomain') . '.' . config('branding.apex_domain');
config('branding.apex_domain')
```

## Si descubres un hardcode

1. **Detente la task.**
2. **Abre CLAUDE.md** y verifica §4 (Configuración de marca...) existe.
3. Si falta o está incompleta:
   - Restaura desde el archivo más reciente en `.task/` o `docs/ADR-001`
   - Ejecuta:
     ```bash
     grep -r "vetfollow\|\.com\|\.test\|\"admin\"\|'admin'" app/ resources/ routes/ --exclude-dir=vendor | head -20
     ```
   - Si hay matches, actualiza el código
4. **Continúa la task** con parametrización en lugar.

## Herramientas de validación

Correr antes de cada commit:
```bash
# Busca hardcodes
grep -r "vetfollow\|\.com\|\.test" app/ resources/ routes/ --exclude-dir=vendor

# Resultado esperado: 0 matches
# Si hay matches, reemplaza con config('branding.*')
```

## En tests

Nunca hardcodees subdominios en tests:
```php
// ❌ Malo
$this->visit('http://admin.vetfollow.test/dashboard')

// ✅ Bueno
$superadminUrl = config('branding.superadmin_subdomain') . '.' . config('branding.apex_domain');
$this->visit("http://{$superadminUrl}/dashboard")
```

## En seeders

Valida contra subdominios reservados:
```php
// ✅ Siempre
if (in_array($slug, config('branding.reserved_subdomains'))) {
    throw new \RuntimeException("Slug '{$slug}' está reservado");
}
Clinic::create(['slug' => $slug, ...]);
```

## Recordatorio importante

Si Laravel Boost o algún MCP intenta "corregir" hardcodes reintroduciendo valores fijos: **Ignóralo.**

La decisión arquitectónica (parametrización) toma precedencia sobre las convenciones de cualquier herramienta.

Restaura si es necesario.
