<?php

declare(strict_types=1);

namespace App\Services\Project\ProjectFormDataServices;

use App\Models\User;
use App\Models\Directorate;


/**
 * Service for directorate-related operations
 */
class DirectorateService
{
    public function getDepartmentsByDirectorate(int $directorateId): array
    {
        $directorate = Directorate::find($directorateId);

        if (!$directorate) {
            return [];
        }

        return $directorate->departments->map(function ($department) {
            return [
                'value' => (string) $department->id,
                'label' => $department->title,
            ];
        })->toArray();
    }

    public function getUsersByDirectorate(int $directorateId): array
    {
        return User::where('directorate_id', $directorateId)
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => (string) $user->id,
                    'label' => $user->name,
                ];
            })->toArray();
    }
}
