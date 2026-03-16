<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Project;
use App\Models\ProjectActivitySchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleProgressUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public ProjectActivitySchedule $schedule,
        public array $updateData
    ) {}
}
