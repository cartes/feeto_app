<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ClientVehicleTemplateExport;
use App\Exports\ProductTemplateExport;
use App\Exports\ServiceTemplateExport;
use App\Http\Requests\ImportClientsWorkbookRequest;
use App\Http\Requests\ImportProductsWorkbookRequest;
use App\Http\Requests\ImportServicesWorkbookRequest;
use App\Imports\ClientVehicleWorkbookImport;
use App\Imports\ProductWorkbookImport;
use App\Imports\ServiceWorkbookImport;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkbookImportController extends Controller
{
    public function downloadClientsTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new ClientVehicleTemplateExport,
            'plantilla-clientes-vehiculos.xlsx',
        );
    }

    public function importClients(ImportClientsWorkbookRequest $request): RedirectResponse
    {
        $import = new ClientVehicleWorkbookImport;

        Excel::import($import, $request->file('workbook'));

        return $this->redirectWithSummary($import->summary());
    }

    public function downloadProductsTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new ProductTemplateExport,
            'plantilla-repuestos.xlsx',
        );
    }

    public function importProducts(ImportProductsWorkbookRequest $request): RedirectResponse
    {
        $import = new ProductWorkbookImport;

        Excel::import($import, $request->file('workbook'));

        return $this->redirectWithSummary($import->summary());
    }

    public function downloadServicesTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new ServiceTemplateExport,
            'plantilla-servicios.xlsx',
        );
    }

    public function importServices(ImportServicesWorkbookRequest $request): RedirectResponse
    {
        $import = new ServiceWorkbookImport;

        Excel::import($import, $request->file('workbook'));

        return $this->redirectWithSummary($import->summary());
    }

    /**
     * @param  array{
     *     kind: string,
     *     processed_rows: int,
     *     skipped_rows: int,
     *     error_rows: int,
     *     errors: array<int, array{row: int, message: string}>
     * }  $summary
     */
    private function redirectWithSummary(array $summary): RedirectResponse
    {
        $message = sprintf(
            'Importación completada: %d filas procesadas, %d omitidas y %d con error.',
            $summary['processed_rows'],
            $summary['skipped_rows'],
            $summary['error_rows'],
        );

        $flashType = match (true) {
            $summary['processed_rows'] === 0 && $summary['error_rows'] > 0 => 'error',
            $summary['error_rows'] > 0 => 'warning',
            default => 'success',
        };

        return back()
            ->with($flashType, $message)
            ->with('import_summary', $summary);
    }
}
