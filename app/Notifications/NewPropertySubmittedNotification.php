<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPropertySubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Property $property)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New property awaiting verification')
            ->line("{$this->property->name} was just submitted by {$this->property->owner->first_name} {$this->property->owner->last_name}.")
            ->action('Review property', url('/admin/properties'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_property_submitted',
            'property_id' => $this->property->id,
            'property_name' => $this->property->name,
        ];
    }
}
