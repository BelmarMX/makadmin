# Notas de implementación aprendidas

## Mass assignment en User

`email_verified_at` no está en `$fillable` del modelo `User`.

Usar asignación directa:

```php
$user->email_verified_at = now();
$user->save();
```

No usar:

```php
$user->update(['email_verified_at' => now()]);
```

## RFC mexicano

RFC puede ser de 12 caracteres para persona moral o 13 para persona física.

Regla:

```php
'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{2,3}$/i'],
```

## Wayfinder con Inertia forms

`useForm().post()` y `router.post()` esperan `string`, no `RouteDefinition`.

Usar `.url`:

```ts
router.post(clinicRoutes.store().url, data)
form.post(clinicRoutes.store().url)
```

No usar:

```ts
router.post(clinicRoutes.store())
```

## Búsqueda case-insensitive en PostgreSQL

Usar operador `ilike` directamente:

```php
->where('columna', 'ilike', "%{$search}%")
->orWhere('otra', 'ilike', "%{$search}%")
```

`whereIlike()` puede existir, pero `orWhereIlike()` no está definido en el Builder de Eloquent y Larastan puede reportarlo.
