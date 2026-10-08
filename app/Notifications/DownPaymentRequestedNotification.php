<?php

namespace App\Notifications;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DownPaymentRequestedNotification extends Notification
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
        $total = (float) $this->contract->security_deposit + (float) $this->contract->advance_payment;

        return (new MailMessage)
            ->subject('Down payment required to confirm your move-in')
            ->line("Your contract for {$this->contract->property->name} — {$this->contract->rentalSpace->space_number} is ready.")
            ->line('To confirm your move-in, please pay the security deposit and advance payment (total ₱'.number_format($total, 2).').')
            ->line('The unit is only handed over after the owner approves your down payment.')
            ->action('Pay down payment', url('/tenant/contracts/'.$this->contract->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'down_payment_requested',
            'contract_id' => $this->contract->id,
            'property_name' => $this->contract->property->name,
            'security_deposit' => (float) $this->contract->security_deposit,
            'advance_payment' => (float) $this->contract->advance_payment,
        ];
    }
}
