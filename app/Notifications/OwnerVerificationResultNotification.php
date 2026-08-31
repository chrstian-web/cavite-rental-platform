<?php

namespace App\Notifications;

use App\Models\OwnerVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerVerificationResultNotification extends Notification
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
        $mail = (new MailMessage)->subject('Update on your owner verification');

        return match ($this->verification->status) {
            'approved' => $mail
                ->line('Your submitted documents have been approved.')
                ->line("You'll receive a one-time code by email next to finish activating your account.")
                ->action('Check status', url('/owner/verification')),
            'rejected' => $mail
                ->line('Your owner verification was not approved.')
                ->line('Reason: '.$this->verification->rejection_reason)
                ->action('Review and resubmit', url('/owner/verification')),
            'needs_additional_documents' => $mail
                ->line('We need a bit more information before we can verify your account.')
                ->line($this->verification->admin_notes ?? 'Please check your verification page for details.')
                ->action('Upload documents', url('/owner/verification')),
            default => $mail->line('Your owner verification status has been updated.'),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'owner_verification_result',
            'owner_verification_id' => $this->verification->id,
            'status' => $this->verification->status,
        ];
    }
}
