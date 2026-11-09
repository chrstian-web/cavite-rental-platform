<?php

namespace App\Notifications;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationFeeOfferedNotification extends Notification
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
            ->subject('Reservation fee and terms — '.$this->application->property->name)
            ->line('The owner has sent a reservation-fee request for your approved rental application.')
            ->line('Reservation fee: ₱'.number_format((float) $this->application->reservation_fee, 2).'.')
            ->line('Terms and conditions:')
            ->line($this->application->reservation_terms)
            ->line('Review the terms and confirm that you agree before continuing to payment.')
            ->action('Review terms and pay', url('/tenant/applications/'.$this->application->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_fee_offered',
            'application_id' => $this->application->id,
            'property_name' => $this->application->property->name,
            'reservation_fee' => (float) $this->application->reservation_fee,
            'reservation_terms' => $this->application->reservation_terms,
        ];
    }
}
