<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    private string $title;
    private string $body;
    private string $type;
    private array $extra;

    /**
     * Create a new notification instance.
     *
     * @param string $title  Notification title
     * @param string $body   Notification body/message
     * @param string $type   Notification type (e.g. 'transaction_reminder', 'subscription_expiry')
     * @param array  $extra  Additional data to store
     */
    public function __construct(string $title, string $body, string $type = 'general', array $extra = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->extra = $extra;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (stored in `data` column).
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title'   => $this->title,
            'message' => $this->body,
            'type'    => $this->type,
        ], $this->extra);
    }
}
