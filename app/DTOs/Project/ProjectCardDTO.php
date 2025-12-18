<?php

declare(strict_types=1);

namespace App\DTOs\Project;

use Illuminate\Support\Collection;

class ProjectCardDTO
{
    public static function collection(Collection $projects): array
    {
        return $projects->map(fn($project) => [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description ?? trans('global.noRecords'),
            'comment_count' => $project->comments_count,

            'directorate' => [
                'id'    => $project->directorate?->id,
                'title' => $project->directorate?->title ?? 'N/A',
            ],
        ])->toArray();
    }
}
