<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLog;
use App\Services\XenditService;
use Illuminate\Http\Request;

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
     * POST /subscription/create-invoice
     * Create a Xendit Invoice and return the payment URL.
     */
    public function createInvoice(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = config("subscription.plans.{$validated['plan_id']}");

        // Check if there's an existing pending invoice (less than 24h old)
        $pendingLog = $user->subscriptionLogs()
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->where('plan_id', $validated['plan_id'])
            ->first();

        if ($pendingLog && $pendingLog->xendit_invoice_url) {
            return response()->json([
                'data' => [
                    'invoice_url'     => $pendingLog->xendit_invoice_url,
                    'invoice_id'      => $pendingLog->xendit_invoice_id,
                    'subscription_id' => $pendingLog->id,
                    'is_existing'     => true,
                ],
                'message' => 'Invoice pembayaran sebelumnya masih aktif.',
            ]);
        }

        // Generate unique external_id
        $externalId = 'SUB-' . $user->id . '-' . time();

        try {
            $invoice = $this->xendit->createInvoice(
                externalId:  $externalId,
                amount:      $plan['price'],
                description: "Langganan Pro {$plan['name']} - Pencatat Keuangan",
                payerEmail:  $user->email,
                metadata:    [
                    'user_id' => $user->id,
                    'plan_id' => $plan['id'],
                ],
            );

            // Save as pending subscription log
            $log = SubscriptionLog::create([
                'user_id'            => $user->id,
                'type'               => 'payment',
                'xendit_invoice_id'  => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                'status'             => 'pending',
                'plan_id'            => $plan['id'],
                'amount'             => $plan['price'],
                'starts_at'          => now(),
                'ends_at'            => now()->addDays($plan['duration_days']),
            ]);

            return response()->json([
                'data' => [
                    'invoice_url'     => $invoice->getInvoiceUrl(),
                    'invoice_id'      => $invoice->getId(),
                    'subscription_id' => $log->id,
                ],
                'message' => 'Invoice berhasil dibuat. Silakan lanjutkan pembayaran.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat invoice pembayaran.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
}
