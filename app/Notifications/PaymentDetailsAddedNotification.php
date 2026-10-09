<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDetailsAddedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->payment->tenant;

        return (new MailMessage)
            ->subject('A tenant added payment proof')
            ->line("{$tenant->first_name} {$tenant->last_name} added a reference number and screenshot for a ₱".number_format($this->payment->amount, 2).' payment.')
            ->line('Reference number: '.$this->payment->reference_number)
            ->action('View payment', url('/owner/payments/'.$this->payment->id));
    }

    public function toArray(object $notifiable): array
    {
        $contract = $this->payment->contract;

        return [
            'type' => 'payment_details_added',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'payment_type' => $this->payment->payment_type,
            'tenant_name' => trim($this->payment->tenant->first_name.' '.$this->payment->tenant->last_name),
            'property_name' => $contract?->property?->name,
        ];
    }
}
