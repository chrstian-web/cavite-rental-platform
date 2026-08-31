<?php

namespace App\Notifications;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(protected RentalContract $contract)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your rental contract is now active')
            ->line("Your contract for {$this->contract->property->name} — {$this->contract->rentalSpace->space_number} is now active.")
            ->action('View contract', url('/tenant/contracts/'.$this->contract->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_activated',
            'contract_id' => $this->contract->id,
            'property_name' => $this->contract->property->name,
        ];
    }
}
