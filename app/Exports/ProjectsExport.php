<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectsExport implements FromQuery, WithHeadings, WithMapping
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
            'ID',
            'Title',
            'Directorate',
            'Department',
            'Status',
            'Priority',
            'Progress (%)',
            'Total Budget',
            'Remaining Budget',
            'Financial Progress (%)',
            'Days Remaining',
            'Start Date',
            'End Date',
            'Created At',
            'Updated At',
            'Deleted At',
        ];
    }

    public function map($project): array
    {
        $totalBudget = $project->total_budget ?? 0;
        $remainingBudget = $totalBudget - ($project->expenses->sum('amount') + $project->contracts->sum('contract_amount'));
        $financialProgress = $totalBudget > 0 ? (($project->expenses->sum('amount') + $project->contracts->sum('contract_amount')) / $totalBudget * 100) : 0;
        $daysRemaining = $project->end_date ? max(0, $project->end_date->diffInDays(now()) * ($project->end_date > now() ? 1 : -1)) : 0;

        return [
            $project->id,
            $project->title,
            $project->directorate ? $project->directorate->title : 'N/A',
            $project->department ? $project->department->title : 'N/A',
            $project->status ? $project->status->title : 'N/A',
            $project->priority ? $project->priority->title : 'N/A',
            (float) $project->progress,
            $totalBudget,
            $remainingBudget,
            round($financialProgress, 2),
            $daysRemaining,
            $project->start_date ? $project->start_date->format('Y-m-d') : 'N/A',
            $project->end_date ? $project->end_date->format('Y-m-d') : 'N/A',
            $project->created_at ? $project->created_at->format('Y-m-d H:i:s') : 'N/A',
            $project->updated_at ? $project->updated_at->format('Y-m-d H:i:s') : 'N/A',
            $project->deleted_at ? $project->deleted_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }
}
