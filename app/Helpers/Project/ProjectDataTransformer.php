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
        $this->directorateColors  = config('colors.directorate', []);
        $this->priorityColors     = config('colors.priority', []);
        $this->progressColor      = config('colors.progress', 'green');
        $this->budgetColor        = config('colors.budget', 'blue');
        $this->budgetHeadingColors = config('colors.budget_heading', []);
    }

    // ────────────────────────────────────────────────
    // Public Transformers
    // ────────────────────────────────────────────────

    /**
     * Transform a single project for card display (used by JS-rendered cards).
     * The top-level `progress` key is what createCardHTML() reads for the bar.
     */
    public function transformProjectForCard($project): array
    {
        $directorateTitle = $project->directorate?->title ?? 'N/A';
        $directorateId    = $project->directorate?->id;

        $budgetHeadingTitle = $project->budgetHeading?->title ?? 'N/A';
        $budgetHeadingId    = $project->budgetHeading?->id;
        $budgetHeadingColor = $budgetHeadingId && isset($this->budgetHeadingColors[$budgetHeadingId])
            ? $this->budgetHeadingColors[$budgetHeadingId]
            : '#6B7280';

        $priorityValue = $project->priority?->title ?? 'N/A';
        $priorityColor = $this->priorityColors[$priorityValue] ?? '#6B7280';

        // Calculate once — contracts + schedules must already be eager-loaded
        // by the caller (ProjectService::getFilteredProjectsData) to avoid N+1
        $physicalProgress = $project->calculatePhysicalProgress();

        return [
            'id'          => $project->id,
            'title'       => $project->title,
            'description' => $project->description ?? trans('global.noRecords'),

            // Top-level progress value read by createCardHTML() for the bar
            'progress'    => $physicalProgress,

            'directorate' => [
                'title' => $directorateTitle,
                'id'    => $directorateId,
            ],
            'budget_heading' => [
                'title' => $budgetHeadingTitle,
                'id'    => $budgetHeadingId,
            ],
            'budget_heading_color' => $budgetHeadingColor,

            'fields'          => $this->buildCardFields($project, $priorityValue, $priorityColor, $physicalProgress),
            'comment_count'   => $project->comments_count ?? 0,
            'contracts_count' => $project->contracts_count ?? 0,

            // Passed through to Blade component when rendered server-side
            'arrayColumnColor' => $this->getColorConfig(),
        ];
    }

    /**
     * Transform a collection of projects for card display.
     */
    public function transformForCards(Collection $projects): array
    {
        return $projects->map(fn($p) => $this->transformProjectForCard($p))->all();
    }

    /**
     * Transform a collection of projects for table display.
     */
    public function transformForTable(Collection $projects): array
    {
        return $projects->map(function ($project) {
            $directorateTitle        = $project->directorate?->title ?? 'N/A';
            $directorateId           = $project->directorate?->id;
            $directorateDisplayColor = $this->directorateColors[$directorateId] ?? 'gray';

            $priorityValue        = $project->priority?->title ?? 'N/A';
            $priorityDisplayColor = $this->priorityColors[$priorityValue] ?? '#6B7280';

            $physicalProgress = $project->calculatePhysicalProgress();

            return [
                'id'    => $project->id,
                'title' => $project->title,

                // Top-level progress for table rows that may render a bar
                'progress'    => $physicalProgress,

                'directorate' => [['title' => $directorateTitle, 'color' => $directorateDisplayColor]],
                'fields'      => $this->buildTableFields($project, $priorityValue, $priorityDisplayColor, $physicalProgress),
            ];
        })->all();
    }

    /**
     * Return the full color configuration array.
     */
    public function getColorConfig(): array
    {
        return [
            'title'          => '#9333EA',
            'progress'       => 'green',
            'budget'         => 'blue',
            'directorate'    => $this->directorateColors,
            'priority'       => $this->priorityColors,
            'budget_heading' => $this->budgetHeadingColors,
        ];
    }

    // ────────────────────────────────────────────────
    // Private Field Builders
    // ────────────────────────────────────────────────

    /**
     * Build the fields array shown in the accordion of each card.
     * Progress is passed in from the caller so we don't call calculatePhysicalProgress() twice.
     */
    private function buildCardFields($project, string $priorityValue, string $priorityColor, float $physicalProgress): array
    {
        return [
            [
                'label' => trans('global.project.fields.start_date'),
                'key'   => 'start_date',
                'value' => $project->start_date?->format('Y-m-d') ?? 'N/A',
            ],
            [
                'label' => trans('global.project.fields.end_date'),
                'key'   => 'end_date',
                'value' => $project->end_date?->format('Y-m-d') ?? 'N/A',
            ],
            [
                'label' => trans('global.project.fields.priority_id'),
                'key'   => 'priority',
                'value' => $priorityValue,
                'color' => $priorityColor,
            ],
            [
                'label' => trans('global.project.fields.physical_progress'),
                'key'   => 'progress',
                'value' => round($physicalProgress, 2) . '%',
            ],
            [
                'label' => trans('global.project.fields.project_manager'),
                'key'   => 'project_manager',
                'value' => $project->projectManager?->name ?? $project->project_manager ?? 'N/A',
            ],
            [
                'label' => 'Location',
                'key'   => 'location',
                'value' => $project->location ?? 'N/A',
            ],
        ];
    }

    /**
     * Build the fields array shown in the table row details.
     * Progress is passed in from the caller so we don't call calculatePhysicalProgress() twice.
     */
    private function buildTableFields($project, string $priorityValue, string $priorityDisplayColor, float $physicalProgress): array
    {
        return [
            [
                'title' => trans('global.project.fields.start_date') . ': ' .
                    ($project->start_date?->format('Y-m-d') ?? 'N/A'),
                'color' => 'gray',
            ],
            [
                'title' => trans('global.project.fields.end_date') . ': ' .
                    ($project->end_date?->format('Y-m-d') ?? 'N/A'),
                'color' => 'gray',
            ],
            [
                'title' => trans('global.project.fields.latest_budget') . ': ' .
                    (is_numeric($project->budget) ? number_format((float) $project->budget, 2) : 'N/A'),
                'color' => $this->budgetColor,
            ],
            [
                'title' => trans('global.project.fields.priority_id') . ': ' . $priorityValue,
                'color' => $priorityDisplayColor,
            ],
            [
                'title' => trans('global.project.fields.physical_progress') . ': ' . round($physicalProgress, 2) . '%',
                'color' => $this->progressColor,
            ],
            [
                'title' => trans('global.project.fields.project_manager') . ': ' .
                    ($project->projectManager?->name ?? 'N/A'),
                'color' => 'gray',
            ],
        ];
    }
}
