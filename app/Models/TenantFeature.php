<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'feature', 'is_enabled'])]
class TenantFeature extends Model
{
    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public const FEATURES = [
        'ocr_enabled' => 'Lectura de patentes con IA',
        'appointments_enabled' => 'Módulo de citas',
        'whatsapp_enabled' => 'Notificaciones WhatsApp',
        'smart_reception_enabled' => 'Recepción inteligente',
        'inventory_enabled' => 'Módulo de inventario',
        'clients_enabled' => 'Módulo de clientes',
        'commercial_quotes_enabled' => 'Cotizaciones comerciales y aprobación del cliente',
        'commercial_reports_enabled' => 'Reportes comerciales avanzados',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
