<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTransactionReminder extends Command
{
    protected $signature = 'app:send-transaction-reminder';
    protected $description = 'Send push notification to users who have not recorded any transaction today';

    public function handle(FcmService $fcmService): int
    {
        $this->info('Checking users without transactions today...');

        // Get all users with FCM token who haven't recorded a transaction today
        $users = User::whereNotNull('fcm_token')
            ->whereDoesntHave('transactions', function ($query) {
                $query->whereDate('created_at', today());
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('All users have recorded transactions today. No reminders needed.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $success = $fcmService->sendToDevice(
                $user->fcm_token,
                '📝 Jangan Lupa Catat Keuangan!',
                "Hai {$user->name}! Kamu belum mencatat pengeluaran hari ini. Yuk catat sekarang supaya keuanganmu tetap terkontrol 💰",
                ['type' => 'transaction_reminder'],
            );

            if ($success) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info("Done! Sent: {$sent}, Failed: {$failed}, Total users: {$users->count()}");
        Log::info("Transaction Reminder: Sent {$sent}, Failed {$failed}");

        return self::SUCCESS;
    }
}
