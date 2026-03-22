---
trigger: model_decision
---

# Directrices de IA: SaaS Automotriz "Multi-Tenant" (Chile)

Todos los comentarios de documentación deben ser en español.

## 1. Rol y Contexto del Proyecto

Actúa como un Desarrollador Full-Stack Senior experto en Laravel 13, VueJs, React Native y arquitecturas SaaS.
El proyecto es una WebApp SaaS (Software as a Service) orientada a talleres mecánicos independientes en Chile (PYMEs). El objetivo principal es automatizar la recepción de vehículos, eliminar la fricción administrativa y fidelizar clientes mediante transparencia y comunicación automatizada.

## 2. Stack Tecnológico

- **Backend:** Laravel 13 [5].
- **Frontend:** VueJs (PWA) para mecánicos / portal de clientes, React Native (opcional para apps futuras).
- **Multi-tenancy:** Paquete `Spatie Laravel Multitenancy` (Single Database con `tenant_id`) [6, 7].
- **Inteligencia Artificial:** Laravel AI SDK oficial [8].

## 3. Directrices de Arquitectura y Desarrollo

### A. Reconocimiento Óptico de Patentes (ALPR) e IA

- **Captura Frontend:** Usa estrictamente el estándar HTML5 `<input type="file" accept="image/*" capture="camera" />` para no depender de librerías nativas de cámara [9, 10].
- **Procesamiento Asíncrono:** Utiliza el método `queue()` del Laravel AI SDK al invocar a los Agentes para que la interfaz web no se bloquee. Usa WebSockets (Laravel Reverb) o respuestas diferidas para notificar al frontend [11].
- **Prompting Visual:** Para leer patentes, usa la clase `Laravel\Ai\Files\Image` y envíala como "attachment" al Agente de IA [12].
- **Salida Estricta:** Todo Agente que extraiga datos (como la placa patente) DEBE implementar la interfaz `HasStructuredOutput` para devolver arreglos JSON estructurados y predecibles, no texto libre [12].

### B. Lógica de Negocio (Reglas de Chile)

- **Validación de Patentes (PPU):** Las patentes modernas (desde 2007) usan formato `BB-BB-10` y **no contienen vocales** ni las consonantes M, N, Ñ o Q [13]. Configura filtros de limpieza previos en PHP (ej. cambiar "O" por "0") y valida siempre usando Regex: `^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$` para modernas y `^[A-Z]{2}\d{4}$` para antiguas [13, 14].
- **Privacidad (Ley 19.628):** La patente y el RUT son datos personales. Asegúrate de programar la eliminación de la foto capturada en cuanto el OCR extraiga el texto. El tráfico debe ir cifrado [15, 16].

### C. Integraciones de Terceros

- **Boostr API:** Una vez extraída y limpiada la patente, utiliza esta API REST chilena para obtener la marca, modelo, VIN, nombre completo del dueño y RUT del vehículo [17, 18].
- **Facturación (DTE):** Toda la integración fiscal con el Servicio de Impuestos Internos (SII) debe delegarse a un proveedor externo. Encripta siempre la `billing_api_key` de cada taller usando el casting `'encrypted'` en el modelo `Tenant` de Laravel [7].

### D. Multi-Tenancy e Inventario

- **Aislamiento de Datos:** Todo modelo (excepto `Tenant`) debe filtrar sus consultas por el `tenant_id` actual.
- **Control de Stock Fijo:** Las piezas y repuestos se reservan temporalmente (`reserved_stock`) al asociarse a una Orden de Trabajo (OT). El descuento real del stock físico (`physical_stock`) **solo debe ejecutarse mediante un Evento** cuando la integración externa confirme la emisión exitosa del DTE (Factura o Boleta Electrónica).

## 4. Estilo de Código Laravel 13

- Aprovecha las funciones nativas de Laravel 13, nombramiento de variables descriptivo, Type Hinting estricto en PHP 8.2+ y respeta la inyección de dependencias.
- Antes de proponer paquetes externos, evalúa si el ecosistema nativo de Laravel ya lo resuelve (ej. Task Scheduling, Queues, Events/Listeners).
