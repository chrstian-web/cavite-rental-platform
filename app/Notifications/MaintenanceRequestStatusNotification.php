<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected MaintenanceRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on your maintenance request')
            ->line("Your {$this->request->category} request is now: ".str($this->request->status)->replace('_', ' ').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_request_status',
            'maintenance_request_id' => $this->request->id,
            'status' => $this->request->status,
        ];
    }
}
