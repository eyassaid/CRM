<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to people added to a project's helpers (assignees) by the team leader or an admin.
 * Queued: the `queue` service in docker-compose.yml sends it in the background.
 */
class ProjectAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public User $assignedBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You were assigned to a project: {$this->project->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy->name} assigned you to the project \"{$this->project->name}\" "
                ."of {$this->project->client->name}.")
            ->line('You can now see and work on it in the CRM.');
    }
}
