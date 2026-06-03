---
name: feature-branding
description: Branding del tenant — logo, color primario, link al espacio público en dashboard
metadata:
  type: project
---

Feature implementada en 2026-06-03 para admins de tenant.

**Archivos creados:**
- `database/migrations/2026_06_03_000001_add_branding_to_tenants_table.php` — añade `primary_color` (string 7) y `logo_path` (string nullable) a `tenants`
- `app/Http/Controllers/TenantBrandingController.php` — `updateColor()`, `uploadLogo()`, `deleteLogo()`

**Archivos modificados:**
- `app/Models/Tenant.php` — método `logoUrl()` que devuelve la URL pública del logo
- `app/Http/Controllers/TallerDashboardController.php` — pasa `public_url` en `tenant` data
- `app/Http/Controllers/TenantSettingsController.php` — pasa `primary_color`, `logo_path`, `logo_url`, `canAccessBranding`
- `app/Http/Controllers/PublicBookingController.php` — pasa `primary_color` y `logo_url` al tenant landing
- `routes/web.php` — rutas `PATCH /settings/branding/color`, `POST /settings/branding/logo`, `DELETE /settings/branding/logo`
- `resources/js/Components/SettingsSectionTabs.vue` — pestaña "Apariencia" con prop `canAccessBranding`
- `resources/js/Pages/Settings/Index.vue` — tab branding con color picker y upload de logo
- `resources/js/Pages/Dashboard.vue` — banner con link al espacio público del taller
- `resources/js/Pages/Public/TenantLanding.vue` — logo condicional + color dinámico en botones CTA

**Why:** El admin del taller necesitaba ver su espacio público directamente desde el dashboard y personalizar la apariencia de su landing page pública.

**How to apply:** `canAccessBranding` es siempre `true` (no está detrás de feature flag). El color primario usa inline styles en TenantLanding, con fallback a `#FF7A00`. El logo se guarda en `storage/public/tenants/{id}/logo.webp`.
