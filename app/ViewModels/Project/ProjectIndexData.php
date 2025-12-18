<?php

declare(strict_types=1);

namespace App\ViewModels\Project;

use App\Models\Directorate;
use App\Models\FiscalYear;
use App\Models\Status;

class ProjectIndexData
{
    public static function make(): array
    {
        return [
            'directorates' => Directorate::pluck('title', 'id'),
            'fiscalYears'  => FiscalYear::pluck('title', 'id'),
            'statuses'     => Status::pluck('title', 'id'),
        ];
    }
}
