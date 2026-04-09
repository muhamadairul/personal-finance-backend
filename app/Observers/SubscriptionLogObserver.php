<?php

namespace App\Observers;

use App\Models\SubscriptionLog;
use App\Models\User;

class SubscriptionLogObserver
{
    /**
     * Handle the SubscriptionLog "created" event.
     */
    public function created(SubscriptionLog $log): void
    {
        $this->syncUserProStatus($log->user);
    }

    /**
     * Handle the SubscriptionLog "updated" event.
     */
    public function updated(SubscriptionLog $log): void
    {
        $this->syncUserProStatus($log->user);
    }

    /**
     * Handle the SubscriptionLog "deleted" event.
     */
    public function deleted(SubscriptionLog $log): void
    {
        $this->syncUserProStatus($log->user);
    }

    /**
     * Find the latest active (paid + not expired) subscription log
     * and sync the user's is_pro + subscription_until fields.
     */
    private function syncUserProStatus(User $user): void
    {
        $latestActive = $user->subscriptionLogs()
            ->where('status', 'paid')
            ->where('ends_at', '>', now())
            ->orderBy('ends_at', 'desc')
            ->first();

        $user->updateQuietly([
            'is_pro'             => $latestActive !== null,
            'subscription_until' => $latestActive?->ends_at,
        ]);
    }
}
