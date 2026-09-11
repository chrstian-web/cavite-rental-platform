<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmittedNotification extends Notification
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
            ->subject('A tenant submitted a payment for review')
            ->line("{$tenant->first_name} {$tenant->last_name} submitted a payment of ₱".number_format($this->payment->amount, 2).' for review.')
            ->line('Reference number: '.$this->payment->reference_number)
            ->action('Review payment', url('/owner/payments/'.$this->payment->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_submitted',
            'payment_id' => $this->payment->id,
            'rental_contract_id' => $this->payment->rental_contract_id,
            'amount' => (string) $this->payment->amount,
        ];
    }
}
