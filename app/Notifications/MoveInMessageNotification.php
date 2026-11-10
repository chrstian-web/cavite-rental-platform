<?php

namespace App\Notifications;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveInMessageNotification extends Notification
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
            ->subject('A message from your owner before move-in — '.$this->contract->property->name)
            ->line('Your owner left a final message before you move in:')
            ->line($this->contract->move_in_message)
            ->action('View contract', url('/tenant/contracts/'.$this->contract->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'move_in_message',
            'contract_id' => $this->contract->id,
            'property_name' => $this->contract->property->name,
            'message' => $this->contract->move_in_message,
        ];
    }
}
