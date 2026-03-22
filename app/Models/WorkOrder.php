<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use TenantAware;

    protected $fillable = ['vehicle_id', 'status', 'observations'];
}
