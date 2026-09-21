# Spec: /clients renderiza pantalla en blanco

Estado: FASE 1-5 completas. Fix implementado y verificado.

Decisión del usuario (alcance aprobado):
- Implementar tags reales en el backend (no solo blindaje defensivo).
- Agregar Error Boundary global, no solo el fix puntual.

## Fase 1 — Especificación del problema

### Síntoma observado
Al visitar `/clients` (módulo Clientes) la página queda completamente en
blanco, sin ningún mensaje de error visible para el usuario. Ocurre tanto
navegando desde el menú como recargando la URL directamente.

Error de consola:
```
TypeError: Cannot read properties of undefined (reading 'tags')
    at Index-<hash>.js:1:10494
```

### Impacto
- Módulo Clientes 100% inutilizable para **cualquier** tenant que tenga al
  menos un cliente en la tabla `clients.data` (ver Fase 2 — no depende de
  un registro específico "corrupto").
- Sin mensaje de error, sin forma de recuperarse desde la UI (requiere
  navegar fuera de `/clients` manualmente).

### Condición de reproducción
100% reproducible: basta con que el listado pagineado de `/clients`
devuelva al menos un cliente. No se requiere ningún estado de datos
especial ni un flujo previo fallido.

### Comportamiento esperado post-fix
La vista `/clients` renderiza correctamente la tabla de clientes,
mostrando la columna "Señales CRM" vacía o con un placeholder cuando no
hay datos de CRM disponibles para un cliente, sin importar qué campos
opcionales falten en el payload.

### Criterios de aceptación
1. La vista `/clients` renderiza correctamente aunque el backend no
   incluya (o incluya parcialmente) el campo `crm`/`tags` para un
   cliente.
2. Ningún campo opcional/faltante en un registro de cliente puede tumbar
   la vista completa: debe degradar con gracia (ej. columna vacía), no
   crashear.
3. Se agrega un Error Boundary (mecanismo equivalente en Vue —
   `onErrorCaptured` / `app.config.errorHandler`) para que un error de
   renderizado futuro en cualquier vista muestre un mensaje controlado en
   vez de pantalla en blanco.
4. Existe un test que reproduce el caso (cliente sin datos de CRM/tags en
   la respuesta del listado) y falla en el código actual, y pasa después
   del fix.

## Fase 2 — Investigación de causa raíz

### Componente real
`resources/js/Pages/Clients/Index.vue:220`, columna "Señales CRM" del
listado:

```vue
<span v-for="tag in client.crm.tags" :key="tag.label" ...>
```

Este es el único punto del código que lee `.tags`, y lo hace sobre
`client.crm` (no sobre `client` directamente, como sugería la hipótesis
inicial del reporte de QA).

### Causa raíz confirmada: (a) bug de frontend, contrato roto con el backend

El controlador `ClientController::index()` delega la construcción de cada
fila a `ClientCrmService::buildIndexItem()`
(`app/Services/ClientCrmService.php:163-182`), que devuelve **únicamente**:

```php
['id', 'name', 'rut', 'phone', 'email', 'metrics' => [...]]
```

**No existe ninguna clave `crm` en absoluto** en la respuesta del listado
(`grep -rn "'crm'" app/ resources/js/` no encuentra ningún productor de
esa clave en todo el repo). El backend nunca ha tenido un concepto de
"tags" de cliente: el otro método del mismo servicio, `buildProfile()`
(usado por la vista `Show`), expone `crmMetrics` (sin tags), consumido
correctamente por `Show.vue`.

Es decir: `client.crm` es `undefined` **para todos los clientes, siempre**
— no es un problema de un registro puntual sin inicializar. Esto explica
la reproducibilidad del 100%.

### Origen del bug (git blame)
El markup que introduce `client.crm.tags` fue añadido en el commit
`d6e87d1` ("feat: add identification validation and formatting support for
multiple Latin American countries"), que reescribió por completo la tabla
del listado de clientes agregando una columna "Señales CRM" **fuera del
alcance descrito en el mensaje del commit** (identificación/RUT). Todo
indica que fue UI especulativa/adelantada a su backend: se agregó el
markup de la columna de tags sin implementar nunca el campo
correspondiente en `ClientCrmService::buildIndexItem()`.

Hallazgo adicional: existe un test
(`tests/Feature/ClientControllerTest.php:168`,
`test_index_includes_crm_signals_for_each_client`) cuyo **nombre** promete
cubrir "CRM signals", pero sus aserciones solo verifican
`clients.data.0.metrics.*` — nunca `crm` ni `crm.tags`. El test pasa hoy
sin detectar el problema porque no ejercita el contrato roto que rompe el
frontend.

### Hipótesis original de QA (registro parcial desde Recepción) — descartada como causa primaria
Se investigó si un cliente creado desde el flujo de Recepción podía
quedar sin `tags`. No aplica: como se confirmó arriba, ningún cliente
—sin importar cómo fue creado— tiene jamás un campo `tags` ni `crm`,
porque el backend nunca lo genera. El bug no depende del flujo de
creación; es un mismatch de contrato frontend/backend presente desde que
se mezcló la columna "Señales CRM" en el commit `d6e87d1`.

### ¿Hay datos corruptos en producción?
No es necesario un backfill para resolver el crash: el problema no es
"faltan datos en un registro", es que el frontend asume una forma de
payload (`crm.tags`) que el backend jamás ha emitido, para ningún
cliente. La pregunta de backfill queda abierta solo si en Fase 3 se
decide implementar de verdad el feature de "tags de cliente" (ver
alcance propuesto abajo).

## Alcance para Fase 3 (a definir con el usuario)

La causa raíz permite dos caminos de fix, no mutuamente excluyentes:

- **Camino mínimo (recomendado para desbloquear producción ya):**
  tratar la columna "Señales CRM" como UI huérfana. Hacerla resiliente a
  `crm`/`tags` ausente (`client.crm?.tags ?? []`) para que nunca vuelva a
  tumbar la página, sin necesidad de implementar el feature de tags en el
  backend todavía.
- **Camino completo:** además de lo anterior, implementar de verdad
  `crm.tags` en `ClientCrmService::buildIndexItem()` (p.ej. tags como
  "VIP", "Moroso", "Nuevo" derivados de `metrics`), para que la columna
  muestre información real en vez de quedar siempre vacía.

Independiente del camino elegido, se agrega el Error Boundary global (no
existe ninguno hoy: no hay `onErrorCaptured` ni
`app.config.errorHandler` en todo `resources/js`) como red de seguridad
para evitar que futuros errores de render dejen la pantalla en blanco en
cualquier vista de la app, no solo Clientes.

## Fase 3 — Plan de implementación (aprobado)

### 1. Backend: `ClientCrmService::buildIndexItem()`
Agregar la clave `crm.tags` derivada de las métricas ya calculadas en la
misma query del listado (sin queries adicionales, sin N+1):

- `sin_visitas` (tone `gray`) — `visits_count === 0`.
- `frecuente` (tone `sky`) — `visits_count >= 3`.
- `alto_valor` (tone `emerald`) — `total_spent >= 300_000` (CLP).

Umbrales como constantes nombradas en el servicio
(`FREQUENT_VISITS_THRESHOLD`, `HIGH_VALUE_THRESHOLD_CLP`), documentadas
como heurística inicial sujeta a definición de producto — no hay ningún
concepto de tags/VIP/moroso preexistente en el dominio
(`grep -rin "vip\|moroso\|tag" app/Models app/Services` no encuentra
nada), así que se define desde cero con datos que ya existen, evitando
introducir queries o tablas nuevas.

Un cliente puede tener 0, 1 o varios tags; el array nunca es
`null`/`undefined`, siempre `[]` como mínimo.

### 2. Frontend: blindaje defensivo en `Index.vue`
Aunque el backend pasará a emitir siempre `crm.tags`, se mantiene
`client.crm?.tags ?? []` en el template (optional chaining + default) como
defensa en profundidad: ningún campo ausente/parcial en un item del
listado debe poder tumbar la vista completa, incluso ante cambios futuros
del backend.

### 3. Error Boundary global
Nuevo componente `resources/js/Components/ErrorBoundary.vue` usando
`onErrorCaptured` (mecanismo nativo de Vue 3 para este propósito), que
muestra un mensaje de error controlado con opción de volver al dashboard
en vez de dejar la pantalla en blanco. Se envuelve el `<slot />` de
`resources/js/Layouts/TallerLayout.vue` (línea 275) con este componente,
lo que cubre automáticamente **todas** las vistas del taller que usan
este layout compartido (Clientes, Órdenes, Inventario, Reportes, etc.),
no solo `/clients`. Esto responde a la pregunta abierta de Fase 3 sobre
si otras vistas tienen el mismo riesgo: sí, cualquier vista con el mismo
patrón (acceso a propiedades anidadas sin optional chaining) tenía el
mismo riesgo; el boundary lo cubre de forma genérica sin necesidad de
auditar campo por campo cada página.

### 4. Migración/backfill de datos
**No aplica.** La causa raíz no es un dato faltante en una columna de BD:
`tags` no es (ni será) una columna persistida, se calcula al vuelo desde
`metrics` en cada request. No hay estado inconsistente que backfillear.

### 5. Tests
- Backend (`tests/Feature/ClientControllerTest.php`): nuevo test que
  arma un cliente con cero actividad (sin vehículos, sin OTs, sin citas)
  y verifica `clients.data.0.crm.tags` contiene `sin_visitas` — con el
  código actual este assert falla porque `crm` no existe en absoluto en
  la respuesta.
  Se complementa el test existente `test_index_includes_crm_signals_for_each_client`
  para que sí verifique `crm.tags` (hoy solo verifica `metrics.*`, no
  detecta el contrato roto).
- Frontend: el proyecto no tiene runner de tests JS configurado
  (`package.json` no declara vitest/jest, no hay `vitest.config`).
  Verificación del render se hace manualmente vía navegador (Chrome
  automation) contra el servidor de desarrollo, documentada en Fase 5
  con evidencia (captura/consola sin errores).

## Fase 4 — Implementación (resumen de cambios)

- `app/Services/ClientCrmService.php`: `buildIndexItem()` ahora incluye
  `crm.tags`, calculado por el nuevo método privado `buildClientTags()`
  a partir de `visits_count`/`total_spent` ya computados en la misma
  query. Constantes `FREQUENT_VISITS_THRESHOLD` (3) y
  `HIGH_VALUE_THRESHOLD_CLP` (300.000) documentadas como heurística
  inicial.
- `resources/js/Pages/Clients/Index.vue:220`: `client.crm.tags` →
  `client.crm?.tags ?? []` (blindaje defensivo, criterio de aceptación
  #2).
- `resources/js/Components/ErrorBoundary.vue` (nuevo): componente con
  `onErrorCaptured` que muestra un mensaje de error controlado con link
  de vuelta al dashboard en vez de dejar la vista en blanco.
- `resources/js/Layouts/TallerLayout.vue`: el `<slot />` del área de
  contenido principal (línea ~275) se envuelve con `<ErrorBoundary>`,
  protegiendo todas las vistas que usan este layout compartido.
- `tests/Feature/ClientControllerTest.php`: se agregó
  `test_index_never_omits_the_crm_tags_key_for_a_client_without_activity`
  y se reforzó `test_index_includes_crm_signals_for_each_client` con
  aserciones sobre `crm.tags`.

## Fase 5 — Verificación

### Suite de tests
- `php artisan test --filter=ClientControllerTest` → **5 passed (99
  assertions)**.
- Confirmado que los 2 tests nuevos/reforzados **fallan contra el
  código pre-fix** (`git stash` temporal de
  `app/Services/ClientCrmService.php`): `Property
  [clients.data.0.crm.tags] does not exist.` — reproduce exactamente el
  contrato roto descrito en Fase 2.
- Suite completa (`php artisan test`): se detectó un fallo de memoria
  preexistente y no relacionado en `TenantControllerTest`
  (`intervention/image` al decodificar una imagen, `AbstractDecoder.php`
  línea 39). Confirmado que ocurre igual en el árbol sin mis cambios
  (`git stash` completo) — no es una regresión introducida por este fix,
  queda fuera de alcance.
- `npm run build` compila sin errores.

### Verificación manual en navegador (datos reales, no sintéticos)
Se levantó `php artisan serve` contra la base de datos de desarrollo
existente (tenant `cartes`, 2 clientes reales) y se navegó a
`/taller/cartes/clients` con Chrome automation:
- La vista renderiza completamente: tabla con ambos clientes, columna
  "Señales CRM" incluida, sin pantalla en blanco.
- Consola del navegador sin errores ni excepciones
  (`read_console_messages` → "No console errors or exceptions found").
- Ningún cliente de este tenant supera los umbrales de tags (visitas <
  3, gasto $0), por lo que ambos muestran la columna vacía — comportamiento
  esperado del array `[]`, no un crash.

### Criterios de aceptación (Fase 1) — estado final
1. ✅ La vista `/clients` renderiza aunque falte `crm`/`tags` — verificado
   con datos reales donde antes crasheaba 100% de las veces.
2. ✅ Ningún campo opcional faltante tumba la vista — blindaje
   `?.` + `?? []` en frontend, y el backend ahora siempre emite la
   estructura completa.
3. ✅ Error Boundary agregado (`ErrorBoundary.vue` vía
   `onErrorCaptured`), envolviendo todas las vistas bajo `TallerLayout`.
4. ✅ Test que reproduce el caso: falla en código pre-fix
   (`Property [...crm.tags] does not exist`), pasa post-fix — confirmado
   explícitamente revirtiendo el fix temporalmente y re-ejecutando.
