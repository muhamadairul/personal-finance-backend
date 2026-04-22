<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLog;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\FcmService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    /**
     * Handle Xendit Payment Request webhook callback.
     * POST /api/webhooks/xendit/invoice
     *
     * Uses the event/data structure from Payment Request API
     * (same pattern as Kafee project).
     */
    public function handleInvoice(Request $request)
    {
        // 1. Verify x-callback-token (if configured)
        // $callbackToken = $request->header('x-callback-token');
        // $expectedToken = config('services.xendit.webhook_token');

        // if ($expectedToken && (!$callbackToken || !hash_equals($expectedToken, $callbackToken))) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        $event       = $request->input('event');
        $data        = $request->input('data');
        $referenceId = $data['reference_id'] ?? null;
        $status      = strtoupper($data['status'] ?? '');
        $paymentId   = $data['id'] ?? null;

        Log::info('Xendit Webhook', compact('event', 'status', 'referenceId', 'paymentId'));

        if (!$referenceId || !$status) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // 2. Find matching subscription log
        $log = SubscriptionLog::where('xendit_invoice_id', $paymentId)->first();

        // Fallback: search by reference_id stored in xendit_invoice_id
        if (!$log) {
            $log = SubscriptionLog::where('xendit_invoice_id', $referenceId)->first();
        }

        if (!$log) {
            Log::warning("Webhook: Subscription log not found", compact('referenceId', 'paymentId'));
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        // 3. Idempotency check
        if ($log->status === 'paid') {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // 4. Map statuses (Kafee pattern)
        $statusMap = [
            'SUCCEEDED' => 'paid',
            'FAILED'    => 'failed',
            'EXPIRED'   => 'expired',
        ];

        $newStatus = $statusMap[$status] ?? null;

        if (!$newStatus) {
            return response()->json(['message' => 'Unhandled status'], 200);
        }

        // 5. Handle payment
        if ($newStatus === 'paid') {
            $this->handlePaid($log, $data);
        } else {
            $log->update([
                'status' => $newStatus,
                'notes'  => "Payment {$status}",
            ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Handle successful payment.
     */
    private function handlePaid(SubscriptionLog $log, array $data): void
    {
        $user = $log->user;
        $plan = config("subscription.plans.{$log->plan_id}");
        $durationDays = $plan['duration_days'] ?? 30;

        // Stack on top of existing active subscription
        $currentEnd = $user->subscription_until;
        $startsAt   = ($currentEnd && $currentEnd->isFuture()) ? $currentEnd : now();
        $endsAt     = $startsAt->copy()->addDays($durationDays);

        // Extract payment details from webhook (Kafee pattern)
        $pm = $data['payment_method'] ?? [];
        $channel = $log->payment_channel;

        if (isset($pm['virtual_account'])) {
            $channel = $pm['virtual_account']['channel_code'] ?? $channel;
        } elseif (isset($pm['qr_code'])) {
            $channel = 'QRIS';
        } elseif (isset($pm['ewallet'])) {
            $channel = $pm['ewallet']['channel_code'] ?? $channel;
        }

        $log->update([
            'status'          => 'paid',
            'payment_channel' => $channel,
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'notes'           => "Paid via Xendit — {$channel}",
        ]);

        // Observer will auto-sync user is_pro & subscription_until

        // Notify the user who paid
        $this->notifyUser($user, $plan, $channel);

        // Notify admin(s)
        $this->notifyAdmins($user, $plan, $channel);
    }

    /**
     * Send database notification + FCM push to the user who paid.
     */
    private function notifyUser(User $user, ?array $plan, string $channel): void
    {
        try {
            $planName = $plan['name'] ?? 'Pro';
            $title = '🎉 Upgrade Pro Berhasil!';
            $body = "Selamat! Kamu berhasil upgrade ke paket {$planName} via {$channel}. Nikmati semua fitur premium!";

            // 1. Save to database
            $user->notify(new GeneralNotification($title, $body, 'subscription_paid', ['plan' => $planName, 'channel' => $channel]));

            // 2. FCM push
            if ($user->fcm_token) {
                app(FcmService::class)->sendToDevice(
                    $user->fcm_token,
                    $title,
                    $body,
                    ['type' => 'subscription_paid'],
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Send Filament database notification + FCM push to admin users.
     */
    private function notifyAdmins(User $user, ?array $plan, string $channel): void
    {
        try {
            $planName = $plan['name'] ?? 'Pro';
            $admins = User::where('is_admin', true)->get();

            if ($admins->isEmpty()) {
                return;
            }

            // 1. Filament Database Notification (visible in admin panel bell icon)
            Notification::make()
                ->title('🎉 Upgrade Pro Berhasil')
                ->body("{$user->name} berhasil upgrade ke paket {$planName} via {$channel}")
                ->success()
                ->sendToDatabase($admins);

            // 2. FCM Push Notification to admin devices
            $fcmService = app(FcmService::class);
            foreach ($admins->whereNotNull('fcm_token') as $admin) {
                $fcmService->sendToDevice(
                    $admin->fcm_token,
                    '🎉 Upgrade Pro Berhasil',
                    "{$user->name} berhasil upgrade ke paket {$planName} via {$channel}",
                    ['type' => 'admin_subscription_paid'],
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
