<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => AuditLog::query()
                ->with('user:id,name,email')
                ->latest('created_at')
                ->paginate(50),
        ]);
    }
}
