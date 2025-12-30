<?php

namespace App\DTOs\Task;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskFilterDTO
{
    public function __construct(
        public readonly string $activeView,
        public readonly ?int $directorateId,
        public readonly ?int $departmentId,
        public readonly ?int $priorityId,
        public readonly ?string $projectId,
        public readonly ?Carbon $startDate,
        public readonly ?Carbon $endDate,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            activeView: session('task_view_preference', 'board'),
            directorateId: $request->input('directorate_id'),
            departmentId: $request->input('department_id'),
            priorityId: $request->input('priority_id'),
            projectId: $request->input('project_id'),
            startDate: $request->filled('date_start')
                ? Carbon::parse($request->date_start)->startOfDay()
                : null,
            endDate: $request->filled('date_end')
                ? Carbon::parse($request->date_end)->endOfDay()
                : null,
        );
    }
}
