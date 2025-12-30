<?php

declare(strict_types=1);

namespace App\DTOs\Project;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class ProjectDTO
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?int $directorate_id = null,
        public readonly ?int $department_id = null,
        public readonly ?int $budget_heading_id = null,
        public readonly ?int $status_id = null,
        public readonly ?int $priority_id = null,
        public readonly ?int $fiscal_year_id = null,
        public readonly ?int $project_manager_id = null,
        public readonly ?Carbon $start_date = null,
        public readonly ?Carbon $end_date = null,
        public readonly ?float $budget = null,
        public readonly ?float $total_budget = null,
        public readonly ?float $progress = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            directorate_id: isset($data['directorate_id']) ? (int) $data['directorate_id'] : null,
            department_id: isset($data['department_id']) ? (int) $data['department_id'] : null,
            budget_heading_id: isset($data['budget_heading_id']) ? (int) $data['budget_heading_id'] : null,
            status_id: isset($data['status_id']) ? (int) $data['status_id'] : null,
            priority_id: isset($data['priority_id']) ? (int) $data['priority_id'] : null,
            fiscal_year_id: isset($data['fiscal_year_id']) ? (int) $data['fiscal_year_id'] : null,
            project_manager_id: isset($data['project_manager_id']) ? (int) $data['project_manager_id'] : null,
            start_date: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            end_date: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            total_budget: isset($data['total_budget']) ? (float) $data['total_budget'] : null,
            progress: isset($data['progress']) ? (float) $data['progress'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'directorate_id' => $this->directorate_id,
            'department_id' => $this->department_id,
            'budget_heading_id' => $this->budget_heading_id,
            'status_id' => $this->status_id,
            'priority_id' => $this->priority_id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'project_manager_id' => $this->project_manager_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'budget' => $this->budget,
            'total_budget' => $this->total_budget,
            'progress' => $this->progress,
        ], fn($value) => $value !== null);
    }
}
