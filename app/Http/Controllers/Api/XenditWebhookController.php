<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLog;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    /**
     * Handle Xendit Invoice webhook callback.
     * POST /api/webhooks/xendit/invoice
     *
     * This endpoint is called by Xendit when an invoice status changes
     * (PAID, EXPIRED, FAILED). It verifies the callback token, finds
     * the matching subscription log, and updates statuses accordingly.
     */
    public function handleInvoice(Request $request)
    {
        // 1. Verify x-callback-token
        $callbackToken = $request->header('x-callback-token');
        $expectedToken = config('services.xendit.webhook_token');

        // If webhook token is configured, verify it
        if ($expectedToken && (!$callbackToken || !hash_equals($expectedToken, $callbackToken))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload   = $request->all();
        $status    = $payload['status'] ?? null;
        $invoiceId = $payload['id'] ?? null;

        if (!$invoiceId) {
            return response()->json(['message' => 'Missing invoice ID'], 400);
        }

        // 2. Find matching subscription log
        $log = SubscriptionLog::where('xendit_invoice_id', $invoiceId)->first();

        if (!$log) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // 3. Idempotency check — don't re-process already paid invoices
        if ($log->status === 'paid') {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // 4. Handle status changes
        if ($status === 'PAID') {
            $this->handlePaid($log, $payload);
        } elseif (in_array($status, ['EXPIRED', 'FAILED'])) {
            $log->update([
                'status' => strtolower($status),
                'notes'  => "Invoice {$status}",
            ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Handle a successful payment.
     */
    private function handlePaid(SubscriptionLog $log, array $payload): void
    {
        $user = $log->user;
        $plan = config("subscription.plans.{$log->plan_id}");
        $durationDays = $plan['duration_days'] ?? 30;

        // Calculate starts_at & ends_at — stack on top of existing subscription
        $currentEnd = $user->subscription_until;
        $startsAt   = ($currentEnd && $currentEnd->isFuture()) ? $currentEnd : now();
        $endsAt     = $startsAt->copy()->addDays($durationDays);

        $paymentChannel = $payload['payment_channel'] ?? $payload['payment_method'] ?? 'unknown';

        $log->update([
            'status'          => 'paid',
            'payment_method'  => $payload['payment_method'] ?? null,
            'payment_channel' => $paymentChannel,
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'notes'           => "Paid via Xendit — {$paymentChannel}",
        ]);

        // Observer will auto-sync user is_pro & subscription_until

        // Send notification to admin(s) in Filament
        $this->notifyAdmins($user, $plan, $paymentChannel);
    }

    /**
     * Send Filament database notification to all admin users.
     */
    private function notifyAdmins(User $user, ?array $plan, string $channel): void
    {
        try {
            $planName = $plan['name'] ?? 'Pro';

            // Notify all users (admins) — in a real app you might filter by role
            $admins = User::all();

            Notification::make()
                ->title('🎉 Upgrade Pro Berhasil')
                ->body("{$user->name} berhasil upgrade ke paket {$planName} via {$channel}")
                ->success()
                ->sendToDatabase($admins);
        } catch (\Exception $e) {
            // Silently fail — notification is not critical
            report($e);
        }
    }
}
