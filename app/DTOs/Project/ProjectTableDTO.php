<?php

declare(strict_types=1);

namespace App\DTOs\Project;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectTableDTO
{
    public static function collection(Collection $projects): array
    {
        $directorateColors = config('colors.directorate');
        $priorityColors    = config('colors.priority');
        $progressColor     = config('colors.progress');
        $budgetColor       = config('colors.budget');

        return $projects->map(
            fn(Project $project) => self::fromModel(
                $project,
                $directorateColors,
                $priorityColors,
                $progressColor,
                $budgetColor
            )
        )->toArray();
    }

    private static function fromModel(
        Project $project,
        array $directorateColors,
        array $priorityColors,
        string $progressColor,
        string $budgetColor
    ): array {
        $directorateId    = $project->directorate?->id;
        $directorateTitle = $project->directorate?->title ?? 'N/A';

        $priorityTitle = $project->priority?->title ?? 'N/A';

        return [
            'id' => $project->id,
            'title' => $project->title,

            'directorate' => [[
                'title' => $directorateTitle,
                'color' => $directorateColors[$directorateId] ?? 'gray',
            ]],

            'fields' => [
                self::field(
                    trans('global.project.fields.start_date'),
                    $project->start_date?->format('Y-m-d'),
                    'gray'
                ),
                self::field(
                    trans('global.project.fields.end_date'),
                    $project->end_date?->format('Y-m-d'),
                    'gray'
                ),
                self::field(
                    trans('global.project.fields.latest_budget'),
                    self::money($project->budget),
                    $budgetColor
                ),
                self::field(
                    trans('global.project.fields.priority_id'),
                    $priorityTitle,
                    $priorityColors[$priorityTitle] ?? '#6B7280'
                ),
                self::field(
                    trans('global.project.fields.physical_progress'),
                    self::percentage($project->progress),
                    $progressColor
                ),
                self::field(
                    trans('global.project.fields.project_manager'),
                    $project->projectManager->name ?? 'N/A',
                    'gray'
                ),
            ],
        ];
    }

    private static function field(string $label, ?string $value, string $color): array
    {
        return [
            'title' => $label . ': ' . ($value ?: 'N/A'),
            'color' => $color,
        ];
    }

    private static function money($value): string
    {
        return is_numeric($value)
            ? number_format((float) $value, 2)
            : 'N/A';
    }

    private static function percentage($value): string
    {
        return is_numeric($value)
            ? $value . '%'
            : 'N/A';
    }
}
