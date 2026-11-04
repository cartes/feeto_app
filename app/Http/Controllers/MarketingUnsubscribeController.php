<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;

class MarketingUnsubscribeController extends Controller
{
    /**
     * Da de baja al taller de los correos de marketing (enlace firmado).
     */
    public function __invoke(Tenant $tenant): View
    {
        if ($tenant->marketing_opt_out_at === null) {
            $tenant->forceFill(['marketing_opt_out_at' => now()])->save();
        }

        return view('marketing-unsubscribed', ['tenant' => $tenant]);
    }
}
