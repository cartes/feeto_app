<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class TenantBrandingController extends Controller
{
    public function updateColor(Request $request): RedirectResponse
    {
        $request->validate([
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tenant = Tenant::current();

        Log::info('updateColor: Debugging connection info', [
            'tenant_id' => $tenant?->id,
            'connection_name' => $tenant?->getConnectionName(),
            'database_name' => $tenant?->getConnection()?->getDatabaseName(),
            'table_name' => $tenant?->getTable(),
            'primary_color_input' => $request->primary_color,
        ]);

        $updated = $tenant->update(['primary_color' => $request->primary_color]);

        Log::info('updateColor: Save result', [
            'updated' => $updated,
            'fresh_color' => $tenant->fresh()?->primary_color,
        ]);

        return back()->with('success', 'Color del taller actualizado correctamente.');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
        ]);

        $tenant = Tenant::current();

        $uploaded = $request->file('logo');
        $directory = "tenants/{$tenant->id}";
        $filename = 'logo.webp';
        $path = "{$directory}/{$filename}";

        try {
            $image = Image::decode($uploaded->getRealPath());

            if ($image->width() > 400) {
                $image->scaleDown(width: 400);
            }

            Storage::disk('public')->put($path, $image->encode(new WebpEncoder(quality: 85))->toString());
        } catch (\Throwable $e) {
            Log::warning("Logo upload image processing failed for tenant {$tenant->id}: ".$e->getMessage());

            $extension = $uploaded->getClientOriginalExtension() ?: 'jpg';
            $filename = 'logo.'.$extension;
            $path = "{$directory}/{$filename}";
            Storage::disk('public')->putFileAs($directory, $uploaded, $filename);
        }

        if ($tenant->logo_path && $tenant->logo_path !== $path) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $tenant->update(['logo_path' => $path]);

        return back()->with('success', 'Logo del taller actualizado correctamente.');
    }

    public function deleteLogo(): RedirectResponse
    {
        $tenant = Tenant::current();

        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo eliminado correctamente.');
    }
}
