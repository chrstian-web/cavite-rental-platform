<?php

namespace App\Notifications;

use App\Models\ViewingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ViewingRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected ViewingRequest $viewing)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->viewing->status;
        $line = match ($status) {
            'confirmed' => 'Your viewing request has been confirmed for '.$this->viewing->preferred_date->format('M j, Y').' at '.$this->viewing->preferred_time.'.',
            'rescheduled' => 'Your viewing has been rescheduled to '.$this->viewing->preferred_date->format('M j, Y').' at '.$this->viewing->preferred_time.'.',
            'cancelled' => 'Your viewing request was cancelled.',
            'completed' => 'Your viewing has been marked as completed. Thanks for visiting!',
            default => 'Your viewing request status has been updated.',
        };

        return (new MailMessage)
            ->subject('Update on your viewing request — '.$this->viewing->property->name)
            ->line($line);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'viewing_request_status',
            'viewing_id' => $this->viewing->id,
            'property_name' => $this->viewing->property->name,
            'status' => $this->viewing->status,
        ];
    }
}
