<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendDailyActivityReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestDailyReportController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $email = $validated['email'] ?? null;
        $job = new SendDailyActivityReport(targetEmail: $email, isTest: true);
        $job->handle();
        $recipient = $job->resolveRecipientEmail() ?? 'contacto@tallerflow.cl';

        $mailer = config('mail.default');
        if ($mailer === 'log') {
            $message = "Reporte diario de prueba generado para {$recipient}. Como el entorno está configurado con MAIL_MAILER=log, se guardó en storage/logs/laravel.log.";
        } else {
            $message = "Reporte diario de prueba enviado exitosamente a {$recipient} mediante {$mailer}.";
        }

        return back()->with('success', $message);
    }
}
