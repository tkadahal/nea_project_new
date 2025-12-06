<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Status;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Log;

class TasksExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Task',
            'Context',
            'Status',
            'Priority',
            'Due Date',
            'Progress (%)',
            'Users',
        ];
    }

    public function map($task): array
    {
        try {
            // Use the coalesced status_id from the query
            $status = Status::find($task->status_id);

            // Determine context (Project, Department, or Directorate)
            $context = 'N/A';
            if ($task->projects->isNotEmpty()) {
                $context = $task->projects->pluck('title')->implode(', ');
            } elseif ($task->department_id && $task->department) {
                $context = $task->department->title;
            } elseif ($task->directorate_id && $task->directorate) {
                $context = $task->directorate->title;
            }

            return [
                $task->title ?? 'N/A',
                $context,
                $status ? $status->title : 'N/A',
                $task->priority ? $task->priority->title : 'N/A',
                $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A',
                $task->progress ?? 0,
                $task->users->isNotEmpty() ? $task->users->pluck('name')->implode(', ') : 'No Users',
            ];
        } catch (\Exception $e) {
            Log::error('Error mapping task for export', [
                'task_id' => $task->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return [
                $task->title ?? 'N/A',
                'Error',
                'Error',
                'Error',
                'N/A',
                0,
                'No Users',
            ];
        }
    }
}
