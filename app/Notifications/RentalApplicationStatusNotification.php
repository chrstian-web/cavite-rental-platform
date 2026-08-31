<?php

namespace App\Notifications;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalApplicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected RentalApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->application->status;
        $line = match ($status) {
            'approved' => 'Congratulations! Your rental application has been approved.',
            'rejected' => 'Your rental application was not approved this time.',
            default => 'Your rental application status has been updated to: '.$status,
        };

        $mail = (new MailMessage)
            ->subject('Update on your rental application — '.$this->application->property->name)
            ->line($line);

        if ($this->application->decision_reason) {
            $mail->line('Note from the property owner: '.$this->application->decision_reason);
        }

        return $mail->action('View application', url('/tenant/applications/'.$this->application->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rental_application_status',
            'application_id' => $this->application->id,
            'property_name' => $this->application->property->name,
            'status' => $this->application->status,
        ];
    }
}
