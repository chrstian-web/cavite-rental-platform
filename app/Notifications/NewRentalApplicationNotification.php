<?php

namespace App\Notifications;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRentalApplicationNotification extends Notification
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
        return (new MailMessage)
            ->subject('New rental application received')
            ->line("A new application was submitted for {$this->application->property->name}.")
            ->line("Applicant: {$this->application->user->first_name} {$this->application->user->last_name}")
            ->line("Desired move-in: {$this->application->desired_move_in_date->format('M j, Y')}")
            ->action('Review application', url('/owner/applications/'.$this->application->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_rental_application',
            'application_id' => $this->application->id,
            'property_name' => $this->application->property->name,
            'applicant_name' => $this->application->user->first_name.' '.$this->application->user->last_name,
        ];
    }
}
