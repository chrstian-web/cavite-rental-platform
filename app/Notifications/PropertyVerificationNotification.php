<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyVerificationNotification extends Notification
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
        $verified = $this->property->verification_status === 'verified';

        $mail = (new MailMessage)->subject(
            $verified ? 'Your property has been verified' : 'Your property listing needs attention'
        );

        return $verified
            ? $mail->line("Good news! \"{$this->property->name}\" has been verified and is now visible to renters.")
                ->action('View listing', url('/properties/'.$this->property->slug))
            : $mail->line("\"{$this->property->name}\" was not approved for public listing. Please review your listing details and contact support if you have questions.")
                ->action('Edit property', url('/owner/properties/'.$this->property->id.'/edit'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'property_verification',
            'property_id' => $this->property->id,
            'property_name' => $this->property->name,
            'status' => $this->property->verification_status,
        ];
    }
}
