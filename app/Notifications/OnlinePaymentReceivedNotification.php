<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnlinePaymentReceivedNotification extends Notification
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
        $amount = number_format($this->payment->amount, 2);
        $isDemo = $this->payment->gateway === 'fake';
        $action = $this->payment->rental_application_id
            ? url('/owner/applications/'.$this->payment->rental_application_id)
            : url('/owner/payments/'.$this->payment->id);

        return (new MailMessage)
            ->subject('Online payment received')
            ->line($isDemo
                ? "A payment of ₱{$amount} was recorded ({$this->payment->typeLabel()})."
                : "A tenant paid ₱{$amount} online ({$this->payment->typeLabel()}).")
            ->line($isDemo ? 'The payment is ready for review.' : 'It was confirmed automatically, so no review is needed.')
            ->action($this->payment->rental_application_id ? 'View application' : 'View payment', $action);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'online_payment_received',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'payment_type' => $this->payment->payment_type,
            'tenant_name' => trim($this->payment->tenant->first_name.' '.$this->payment->tenant->last_name),
            'property_name' => $this->payment->application?->property?->name ?? $this->payment->contract?->property?->name,
            'application_id' => $this->payment->rental_application_id,
            'demo_mode' => $this->payment->gateway === 'fake',
            'payment_method' => $this->payment->payment_method,
        ];
    }
}
