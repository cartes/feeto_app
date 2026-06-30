<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TrialRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrialRequestSubmitted
{
    use Dispatchable, SerializesModels;

    /**
     * Crea una nueva instancia del evento.
     */
    public function __construct(public readonly TrialRequest $trialRequest) {}
}
