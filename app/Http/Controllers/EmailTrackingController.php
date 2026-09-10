<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    /**
     * 1x1 transparent GIF bytes.
     */
    private const TRANSPARENT_GIF_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function trackOpen(Request $request, string $token): Response
    {
        $tracking = EmailTracking::where('token', $token)->first();

        if ($tracking) {
            if ($tracking->opened_at === null) {
                $tracking->opened_at = now();
            }
            $tracking->increment('open_count');
            $tracking->save();
        }

        $gifContent = base64_decode(self::TRANSPARENT_GIF_BASE64, true) ?: '';

        return response($gifContent, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Content-Length' => (string) strlen($gifContent),
        ]);
    }

    public function trackClick(Request $request, string $token): RedirectResponse
    {
        $tracking = EmailTracking::with('tenant')->where('token', $token)->first();

        if ($tracking) {
            if ($tracking->clicked_at === null) {
                $tracking->clicked_at = now();
            }
            $tracking->increment('click_count');
            $tracking->save();

            if ($tracking->tenant) {
                $params = ['tenantBySlug' => $tracking->tenant->slug];
                if ($tracking->offer_discount_percent) {
                    $params['discount'] = $tracking->offer_discount_percent;
                }

                return redirect()->route('checkout.show', $params);
            }
        }

        return redirect()->route('home');
    }
}
