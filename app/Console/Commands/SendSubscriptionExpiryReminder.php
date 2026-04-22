<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\FcmService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiryReminder extends Command
{
    protected $signature = 'app:send-subscription-expiry-reminder';
    protected $description = 'Send push notification to users whose subscription is expiring soon or has expired';

    public function handle(FcmService $fcmService): int
    {
        $this->info('Checking subscription expiry...');

        $sentUser = 0;
        $sentAdmin = 0;

        // Case 1: Subscription expiring within 3 days
        $expiringUsers = User::where('is_pro', true)
            ->whereNotNull('subscription_until')
            ->whereBetween('subscription_until', [now(), now()->addDays(3)])
            ->get();

        foreach ($expiringUsers as $user) {
            $daysLeft = (int) now()->diffInDays($user->subscription_until);
            $daysText = $daysLeft <= 0 ? 'hari ini' : "{$daysLeft} hari lagi";

            $title = '🔔 Langganan Pro Akan Berakhir';
            $body = "Langganan Pro-mu akan berakhir {$daysText}. Perpanjang sekarang agar tetap menikmati fitur premium!";

            // 1. Save to database for user
            $user->notify(new GeneralNotification($title, $body, 'subscription_expiry', ['days_left' => $daysLeft]));

            // 2. Send FCM push to user
            if ($user->fcm_token) {
                $fcmService->sendToDevice(
                    $user->fcm_token,
                    $title,
                    $body,
                    ['type' => 'subscription_expiry', 'days_left' => (string) $daysLeft],
                );
                $sentUser++;
            }

            // Notify admins via Filament DB notification
            $this->notifyAdminsExpiring($user, $daysText);
            $sentAdmin++;
        }

        // Case 2: Subscription just expired (subscription_until was yesterday)
        $expiredUsers = User::where('is_pro', true)
            ->whereNotNull('subscription_until')
            ->whereDate('subscription_until', now()->subDays(1))
            ->get();

        foreach ($expiredUsers as $user) {
            $title = '⭐ Langganan Pro Telah Berakhir';
            $body = 'Langganan Pro-mu telah berakhir. Upgrade kembali untuk menikmati fitur ekspor, budget tak terbatas, dan lainnya!';

            // 1. Save to database for user
            $user->notify(new GeneralNotification($title, $body, 'subscription_expired'));

            // 2. Send FCM push to user
            if ($user->fcm_token) {
                $fcmService->sendToDevice(
                    $user->fcm_token,
                    $title,
                    $body,
                    ['type' => 'subscription_expired'],
                );
                $sentUser++;
            }
        }

        $this->info("Done! User notifications: {$sentUser}, Admin notifications: {$sentAdmin}");
        $this->info("Expiring soon: {$expiringUsers->count()}, Just expired: {$expiredUsers->count()}");
        Log::info("Subscription Reminder: User notifs {$sentUser}, Admin notifs {$sentAdmin}");

        return self::SUCCESS;
    }

    /**
     * Notify admin users that a user's subscription is expiring.
     */
    private function notifyAdminsExpiring(User $user, string $daysText): void
    {
        try {
            $admins = User::where('is_admin', true)->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::make()
                ->title('⚠️ Langganan Akan Berakhir')
                ->body("Langganan Pro milik {$user->name} ({$user->email}) akan berakhir {$daysText}")
                ->warning()
                ->sendToDatabase($admins);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
