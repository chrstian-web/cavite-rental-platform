<?php

namespace App\Notifications;

use App\Models\ViewingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewViewingRequestNotification extends Notification
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
        return (new MailMessage)
            ->subject('New viewing request — '.$this->viewing->property->name)
            ->line("{$this->viewing->user->first_name} {$this->viewing->user->last_name} requested a viewing.")
            ->line('Preferred: '.$this->viewing->preferred_date->format('M j, Y').' at '.$this->viewing->preferred_time)
            ->action('Manage viewing requests', url('/owner/viewings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_viewing_request',
            'viewing_id' => $this->viewing->id,
            'property_name' => $this->viewing->property->name,
        ];
    }
}
