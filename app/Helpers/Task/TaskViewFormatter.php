<?php

namespace App\Helpers\Task;

use Illuminate\Support\Collection;
use App\Helpers\Task\TaskListFormatter;
use App\Helpers\Task\TaskBoardFormatter;
use App\Helpers\Task\TaskTableFormatter;
use App\Helpers\Task\TaskCalendarFormatter;

class TaskViewFormatter
{
    public static function format(
        Collection $tasks,
        string $view,
        array $statusColors,
        array $priorityColors
    ): array {
        return match ($view) {
            'board'    => TaskBoardFormatter::handle($tasks, $statusColors, $priorityColors),
            'list'     => TaskListFormatter::handle($tasks),
            'calendar' => TaskCalendarFormatter::handle($tasks, $statusColors),
            'table'    => TaskTableFormatter::handle($tasks, $statusColors, $priorityColors),
            default    => [],
        };
    }
}
