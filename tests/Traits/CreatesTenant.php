<?php

namespace Tests\Traits;

use App\Models\Tenant;
use Illuminate\Support\Facades\URL;

trait CreatesTenant
{
    /**
     * Creates a dummy tenant and makes it current.
     */
    protected function setUpTenant(): Tenant
    {
        $tenant = Tenant::firstOrCreate(
            ['rut_taller' => '12345678-9'],
            [
                'name' => 'Taller Test',
                'slug' => 'taller-test',
                'domain' => 'test.feeto.test',
            ]
        );

        $tenant->makeCurrent();
        URL::defaults(['tenantBySlug' => $tenant->slug]);

        return $tenant;
    }
}
