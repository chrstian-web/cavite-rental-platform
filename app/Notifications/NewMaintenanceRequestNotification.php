<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMaintenanceRequestNotification extends Notification
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
            ->subject('New maintenance request — '.$this->request->property->name)
            ->line(ucfirst($this->request->priority).' priority: '.$this->request->description)
            ->action('View maintenance requests', url('/owner/maintenance'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_maintenance_request',
            'maintenance_request_id' => $this->request->id,
            'property_name' => $this->request->property->name,
        ];
    }
}
