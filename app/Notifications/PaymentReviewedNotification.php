<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReviewedNotification extends Notification
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
        $mail = (new MailMessage)->subject('Update on your payment');
        $amount = number_format($this->payment->amount, 2);

        return match ($this->payment->status) {
            'paid' => $mail
                ->line("Your payment of ₱{$amount} has been approved.")
                ->action('Download receipt', url('/tenant/payments/'.$this->payment->id.'/receipt')),
            'failed' => $mail
                ->line("Your payment of ₱{$amount} was rejected.")
                ->line('Reason: '.$this->payment->review_reason)
                ->action('View payment', url('/tenant/payments/'.$this->payment->id)),
            'pending' => $mail
                ->line("Your payment of ₱{$amount} needs a correction before it can be approved.")
                ->line('Details: '.$this->payment->review_reason)
                ->action('Resubmit payment', url('/tenant/payments/'.$this->payment->id)),
            default => $mail->line('Your payment status has been updated.'),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_reviewed',
            'payment_id' => $this->payment->id,
            'status' => $this->payment->status,
        ];
    }
}
