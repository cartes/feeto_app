<?php

namespace Tests\Traits;

use App\Models\Tenant;

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
                'name'       => 'Taller Test',
                'domain'     => 'test.feeto.test',
            ]
        );

        $tenant->makeCurrent();

        return $tenant;
    }
}
