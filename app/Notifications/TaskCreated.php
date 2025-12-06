<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreated extends Notification
{
    use Queueable;

    protected $task;

    public function __construct($task)
    {
        $this->task = $task->load('projects');
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Task Assigned: ' . ($this->task->title ?? 'Unnamed Task'))
            ->line('You have been assigned to a new task.')
            ->line('**Task Name**: ' . ($this->task->title ?? 'Unnamed Task'))
            ->line('**Project**: ' . ($this->task->projects->isNotEmpty() ? $this->task->projects->pluck('title')->join(', ') : 'None'))
            ->line('**Due Date**: ' . ($this->task->due_date ? \Carbon\Carbon::parse($this->task->due_date)->format('M d, Y') : 'Not set'))
            ->action('View Task', route('admin.task.show', $this->task->id))
            ->line('Thank you for your attention!');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->title ?? 'Unnamed Task',
            'message' => 'You were assigned to task: ' . ($this->task->title ?? 'Unnamed Task'),
            'url' => route('admin.task.show', $this->task->id),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
