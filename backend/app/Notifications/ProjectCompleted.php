<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every admin and to the leader of the client's team when a project is marked done.
 * The email is queued (sent by the `queue` service in docker-compose.yml); the in-app notification is stored right away.
 */
class ProjectCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Invoice $invoice,
        public User $completedBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Only the email goes through the queue; `sync` stores the in-app notification during the request.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Project completed: {$this->project->name}")
            ->line("{$this->completedBy->name} marked the project \"{$this->project->name}\" "
                ."of {$this->project->client->name} as done.")
            ->line('Invoice total: '.number_format($this->invoice->total(), 2))
            // Rendered when the email is sent: invoice PDFs are not stored.
            ->attachData($this->invoice->pdf()->output(), 'invoice.pdf', [
                'mime' => 'application/pdf',
            ]);
    }

    /**
     * Stored in the notifications table and returned by GET /notifications.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->completedBy->name} marked \"{$this->project->name}\" as done.",
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'client_name' => $this->project->client->name,
            'team_name' => $this->project->client->team?->name,
            'completed_by' => $this->completedBy->name,
            'invoice_id' => $this->invoice->id,
            'invoice_total' => $this->invoice->total(),
        ];
    }
}
