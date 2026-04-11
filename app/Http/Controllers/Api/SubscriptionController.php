<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLog;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(private XenditService $xendit) {}

    /**
     * GET /subscription/plans
     * Return available subscription plans.
     */
    public function plans()
    {
        return response()->json([
            'data' => array_values(config('subscription.plans')),
        ]);
    }

    /**
     * GET /subscription/status
     * Check current user's subscription status.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $activeLog = $user->subscriptionLogs()
            ->where('status', 'paid')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();

        return response()->json([
            'is_pro'              => $user->isPro(),
            'subscription_until'  => $user->subscription_until?->toISOString(),
            'active_subscription' => $activeLog,
        ]);
    }

    /**
     * POST /subscription/pay/qris
     * Create QRIS payment → return qr_string for native rendering.
     */
    public function payQris(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = config("subscription.plans.{$validated['plan_id']}");
        $referenceId = 'SUB-' . $user->id . '-' . uniqid();

        try {
            $result = $this->xendit->createQrisPayment($referenceId, $plan['price']);

            $qrString = $result['payment_method']['qr_code']['channel_properties']['qr_string'] ?? null;

            $log = $this->createPendingLog($user, $plan, $referenceId, $result, 'QR_CODE', 'QRIS');

            return response()->json([
                'data' => [
                    'subscription_id' => $log->id,
                    'qr_string'       => $qrString,
                    'amount'          => $plan['price'],
                    'plan_name'       => $plan['name'],
                    'expires_at'      => now()->addMinutes(30)->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('QRIS Payment Error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal membuat pembayaran QRIS.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /subscription/pay/va
     * Create Virtual Account payment → return va_number for display.
     */
    public function payVa(Request $request)
    {
        $validated = $request->validate([
            'plan_id'   => 'required|in:monthly,yearly',
            'bank_code' => 'required|in:BCA,BNI,BRI,MANDIRI,PERMATA',
        ]);

        $user = $request->user();
        $plan = config("subscription.plans.{$validated['plan_id']}");
        $referenceId = 'SUB-' . $user->id . '-' . uniqid();

        try {
            $result = $this->xendit->createVaPayment(
                $referenceId,
                $plan['price'],
                $validated['bank_code'],
                $user->name,
            );

            $vaNumber = $result['payment_method']['virtual_account']
                ['channel_properties']['virtual_account_number'] ?? null;

            $log = $this->createPendingLog(
                $user, $plan, $referenceId, $result,
                'VIRTUAL_ACCOUNT', $validated['bank_code'],
            );

            return response()->json([
                'data' => [
                    'subscription_id' => $log->id,
                    'va_number'       => $vaNumber,
                    'bank_code'       => $validated['bank_code'],
                    'amount'          => $plan['price'],
                    'plan_name'       => $plan['name'],
                    'expires_at'      => now()->addHours(24)->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('VA Payment Error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal membuat pembayaran Virtual Account.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /subscription/pay/ewallet
     * Create E-Wallet payment → return actions URL for deeplink.
     */
    public function payEwallet(Request $request)
    {
        $validated = $request->validate([
            'plan_id'      => 'required|in:monthly,yearly',
            'channel_code' => 'required|in:OVO,DANA,SHOPEEPAY,LINKAJA',
        ]);

        $user = $request->user();
        $plan = config("subscription.plans.{$validated['plan_id']}");
        $referenceId = 'SUB-' . $user->id . '-' . uniqid();

        try {
            $result = $this->xendit->createEwalletPayment(
                $referenceId,
                $plan['price'],
                $validated['channel_code'],
            );

            $actions = $result['actions'] ?? [];
            $deepLinkUrl = null;
            $mobileUrl = null;

            foreach ($actions as $action) {
                if ($action['url_type'] === 'DEEPLINK') {
                    $deepLinkUrl = $action['url'];
                } elseif ($action['url_type'] === 'MOBILE') {
                    $mobileUrl = $action['url'];
                }
            }

            $log = $this->createPendingLog(
                $user, $plan, $referenceId, $result,
                'EWALLET', $validated['channel_code'],
            );

            return response()->json([
                'data' => [
                    'subscription_id' => $log->id,
                    'deep_link_url'   => $deepLinkUrl,
                    'mobile_url'      => $mobileUrl,
                    'actions'         => $actions,
                    'channel_code'    => $validated['channel_code'],
                    'amount'          => $plan['price'],
                    'plan_name'       => $plan['name'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('E-Wallet Payment Error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal membuat pembayaran E-Wallet.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /subscription/check/{id}
     * Check payment status (for mobile polling).
     */
    public function checkStatus($id, Request $request)
    {
        $log = SubscriptionLog::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => $log->status,
            'is_pro' => $log->user->isPro(),
            'subscription_until' => $log->user->subscription_until?->toISOString(),
        ]);
    }

    /**
     * GET /subscription/history
     * Return user's subscription history.
     */
    public function history(Request $request)
    {
        $logs = $request->user()->subscriptionLogs()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $logs]);
    }

    /**
     * Helper: Create a pending subscription log.
     */
    private function createPendingLog($user, array $plan, string $referenceId, array $xenditResult, string $method, string $channel): SubscriptionLog
    {
        return SubscriptionLog::create([
            'user_id'           => $user->id,
            'type'              => 'payment',
            'xendit_invoice_id' => $xenditResult['id'] ?? $referenceId,
            'status'            => 'pending',
            'plan_id'           => $plan['id'],
            'amount'            => $plan['price'],
            'payment_method'    => $method,
            'payment_channel'   => $channel,
            'starts_at'         => now(),
            'ends_at'           => now()->addDays($plan['duration_days']),
        ]);
    }
}
