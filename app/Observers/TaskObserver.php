<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        $task->projects->each->updatePhysicalProgress();
    }

    public function updated(Task $task): void
    {
        $task->projects->each->updatePhysicalProgress();
    }

    public function deleted(Task $task): void
    {
        $task->projects->each->updatePhysicalProgress();
    }
}
