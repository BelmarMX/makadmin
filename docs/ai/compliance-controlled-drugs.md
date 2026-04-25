# Auditoría, soft delete y medicamentos controlados

## Soft delete

- Todos los modelos de dominio usan `SoftDeletes`.
- Borrado lógico debe presentarse como “archivar” cuando sea más claro para el usuario.
- Restaurar requiere ruta y permiso explícito `.restore`.
- `forceDelete()` no debe aparecer en código de aplicación.

## Auditoría

Todos los modelos de dominio implementan `Auditable`.

Eventos registrados:

- created
- updated
- deleted
- restored

Configuración global:

- `ip_address`
- `user_agent`
- `user_id`
- `url`
- `tags = ['clinic:'.clinic_id]`

Retención indefinida. Particionar tabla `audits` cuando supere 10M filas.

## Logs

- Canal default: `daily`.
- Canal `controlled_drugs`: archivo separado, retención mínima 5 años.
- Canal `security`: impersonation, login, cambios de permisos, fallos de aislamiento.

## Medicamentos controlados

Aplica a productos `is_controlled = true` en inventario, fracciones I–V.

### Reglas duras

1. Cada movimiento —entrada, salida, ajuste— genera folio inmutable consecutivo por clínica.
2. Toda salida exige receta ligada, cédula profesional del médico prescriptor y firma del dispensador.
3. La firma del dispensador requiere password re-challenge del usuario autenticado.
4. Los movimientos no se editan.
5. Los movimientos no se borran, ni siquiera con soft delete.
6. Correcciones solo mediante movimiento inverso con justificación obligatoria y referencia al folio original.
7. Kárdex con saldo corrido: cada registro guarda `balance_after` calculado.
8. Cada movimiento guarda hash SHA-256 de `prev_hash + payload` para detectar manipulación directa en BD.
9. Reporte mensual exportable CSV + PDF firmado para COFEPRIS, disponible al día 1 de cada mes.

### Implementación

- Módulo separado: `app/Domain/Inventory/ControlledDrugs/`.
- Tabla dedicada: `controlled_drug_movements`.
- No heredar de `inventory_movements`.
- Migraciones marcadas con comentario `// NOM_CRITICAL`.
- No modificar migraciones críticas sin revisión explícita.
