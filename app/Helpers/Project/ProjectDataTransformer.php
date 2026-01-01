<?php

declare(strict_types=1);

namespace App\Helpers\Project;

use Illuminate\Support\Collection;

class ProjectDataTransformer
{
    private array $directorateColors;
    private array $priorityColors;
    private string $progressColor;
    private string $budgetColor;
    private array $budgetHeadingColors;

    public function __construct()
    {
        $this->directorateColors = config('colors.directorate', []);
        $this->priorityColors = config('colors.priority', []);
        $this->progressColor = config('colors.progress', 'green');
        $this->budgetColor = config('colors.budget', 'blue');
        $this->budgetHeadingColors = config('colors.budget_heading', []);
    }

    /**
     * Transform single project for card display
     */
    public function transformProjectForCard($project): array
    {
        $directorateTitle = $project->directorate?->title ?? 'N/A';
        $directorateId = $project->directorate?->id;

        $budgetHeadingTitle = $project->budgetHeading?->title ?? 'N/A';
        $budgetHeadingId = $project->budgetHeading?->id;
        $budgetHeadingColor = $budgetHeadingId && isset($this->budgetHeadingColors[$budgetHeadingId])
            ? $this->budgetHeadingColors[$budgetHeadingId]
            : '#6B7280';

        $priorityValue = $project->priority?->title ?? 'N/A';
        $priorityColor = $this->priorityColors[$priorityValue] ?? '#6B7280';

        return [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description ?? trans('global.noRecords'),
            'directorate' => [
                'title' => $directorateTitle,
                'id' => $directorateId
            ],
            'budget_heading' => [
                'title' => $budgetHeadingTitle,
                'id' => $budgetHeadingId
            ],
            'budget_heading_color' => $budgetHeadingColor,
            'fields' => $this->buildCardFields($project, $priorityValue, $priorityColor),
            'comment_count' => $project->comments_count ?? 0,
            // These are needed for the Blade component
            'arrayColumnColor' => $this->getColorConfig(),
        ];
    }

    /**
     * Transform projects for card display
     */
    public function transformForCards(Collection $projects): array
    {
        return $projects->map(fn($project) => $this->transformProjectForCard($project))->all();
    }

    /**
     * Transform projects for table display
     */
    public function transformForTable(Collection $projects): array
    {
        return $projects->map(function ($project) {
            $directorateTitle = $project->directorate?->title ?? 'N/A';
            $directorateId = $project->directorate?->id;
            $directorateDisplayColor = $this->directorateColors[$directorateId] ?? 'gray';

            $priorityValue = $project->priority?->title ?? 'N/A';
            $priorityDisplayColor = $this->priorityColors[$priorityValue] ?? '#6B7280';

            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => [['title' => $directorateTitle, 'color' => $directorateDisplayColor]],
                'fields' => $this->buildTableFields($project, $priorityValue, $priorityDisplayColor),
            ];
        })->all();
    }

    /**
     * Get color configuration array
     */
    public function getColorConfig(): array
    {
        return [
            'title' => '#9333EA',
            'progress' => 'green',
            'budget' => 'blue',
            'directorate' => $this->directorateColors,
            'priority' => $this->priorityColors,
            'budget_heading' => $this->budgetHeadingColors,
        ];
    }

    /**
     * Build fields array for card display
     */
    private function buildCardFields($project, string $priorityValue, string $priorityColor): array
    {
        return [
            [
                'label' => trans('global.project.fields.start_date'),
                'key' => 'start_date',
                'value' => $project->start_date?->format('Y-m-d') ?? 'N/A'
            ],
            [
                'label' => trans('global.project.fields.end_date'),
                'key' => 'end_date',
                'value' => $project->end_date?->format('Y-m-d') ?? 'N/A'
            ],
            [
                'label' => trans('global.project.fields.priority_id'),
                'key' => 'priority',
                'value' => $priorityValue,
                'color' => $priorityColor
            ],
            [
                'label' => trans('global.project.fields.physical_progress'),
                'key' => 'progress',
                'value' => is_numeric($project->progress) ? $project->progress . '%' : 'N/A'
            ],
            [
                'label' => trans('global.project.fields.project_manager'),
                'key' => 'project_manager',
                'value' => $project->project_manager ?? 'N/A'
            ],
            [
                'label' => 'Location',
                'key' => 'location',
                'value' => $project->location ?? 'N/A'
            ],
        ];
    }

    /**
     * Build fields array for table display
     */
    private function buildTableFields($project, string $priorityValue, string $priorityDisplayColor): array
    {
        return [
            [
                'title' => trans('global.project.fields.start_date') . ': ' .
                    ($project->start_date?->format('Y-m-d') ?? 'N/A'),
                'color' => 'gray'
            ],
            [
                'title' => trans('global.project.fields.end_date') . ': ' .
                    ($project->end_date?->format('Y-m-d') ?? 'N/A'),
                'color' => 'gray'
            ],
            [
                'title' => trans('global.project.fields.latest_budget') . ': ' .
                    (is_numeric($project->budget) ? number_format((float) $project->budget, 2) : 'N/A'),
                'color' => $this->budgetColor
            ],
            [
                'title' => trans('global.project.fields.priority_id') . ': ' . $priorityValue,
                'color' => $priorityDisplayColor
            ],
            [
                'title' => trans('global.project.fields.physical_progress') . ': ' .
                    (is_numeric($project->progress) ? $project->progress . '%' : 'N/A'),
                'color' => $this->progressColor
            ],
            [
                'title' => trans('global.project.fields.project_manager') . ': ' .
                    ($project->projectManager->name ?? 'N/A'),
                'color' => 'gray'
            ],
        ];
    }
}
