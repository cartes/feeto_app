<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderImage extends Model
{
    protected $fillable = [
        'work_order_id',
        'image_path',
        'notes',
    ];
}
