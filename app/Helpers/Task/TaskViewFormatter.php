<?php

namespace App\Helpers\Task;

use Illuminate\Support\Collection;

class TaskViewFormatter
{
    public static function format(
        Collection $tasks,
        string $view,
        array $statusColors,
        array $priorityColors
    ): array {
        return match ($view) {
            'board' => TaskBoardFormatter::handle($tasks, $statusColors, $priorityColors),
            'list' => TaskListFormatter::handle($tasks),
            'calendar' => TaskCalendarFormatter::handle($tasks, $statusColors),
            'table' => TaskTableFormatter::handle($tasks, $statusColors, $priorityColors),
            default => [],
        };
    }
}
