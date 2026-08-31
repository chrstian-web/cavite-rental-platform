<?php

namespace App\Notifications;

use App\Models\OwnerVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOwnerVerificationSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(protected OwnerVerification $verification)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $owner = $this->verification->user;

        return (new MailMessage)
            ->subject('New owner verification awaiting review')
            ->line("{$owner->first_name} {$owner->last_name} submitted documents for owner verification.")
            ->action('Review submission', url('/admin/owner-verifications/'.$this->verification->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_owner_verification_submitted',
            'owner_verification_id' => $this->verification->id,
            'owner_name' => $this->verification->user->first_name.' '.$this->verification->user->last_name,
        ];
    }
}
