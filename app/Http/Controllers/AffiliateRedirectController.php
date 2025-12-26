<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Services\Affiliate\RevenueTrackerService;
use Illuminate\Http\Request;

/**
 * Affiliate Redirect Controller
 *
 * Handles affiliate link clicks and redirects with tracking.
 */
class AffiliateRedirectController extends Controller
{
    protected RevenueTrackerService $revenueTracker;

    public function __construct(RevenueTrackerService $revenueTracker)
    {
        $this->revenueTracker = $revenueTracker;
    }

    /**
     * Handle affiliate link click and redirect.
     *
     * @param Request $request
     * @param string $shortCode
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request, string $shortCode)
    {
        // Find affiliate link by short code
        $link = AffiliateLink::where('short_code', $shortCode)->first();

        if (!$link) {
            abort(404, 'Affiliate link not found');
        }

        // Track the click
        $this->revenueTracker->trackClick($link, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
        ]);

        // Redirect to original URL
        return redirect()->away($link->original_url);
    }

    /**
     * Track conversion callback (webhook endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackConversion(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'short_code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $link = AffiliateLink::where('short_code', $request->short_code)->first();

        if (!$link) {
            return response()->json(['error' => 'Link not found'], 404);
        }

        $success = $this->revenueTracker->trackConversion($link, $request->amount, [
            'transaction_id' => $request->transaction_id,
        ]);

        if ($success) {
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Failed to track conversion'], 500);
    }
}
