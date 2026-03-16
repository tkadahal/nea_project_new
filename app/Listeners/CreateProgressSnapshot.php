<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ScheduleProgressUpdated;
use App\Models\ProjectScheduleProgressSnapshot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateProgressSnapshot
{
    public function handle(ScheduleProgressUpdated $event): void
    {
        Log::info('CreateProgressSnapshot called', [
            'schedule_id' => $event->schedule->id,
            'progress' => $event->updateData['progress'],
            'previous_progress' => $event->updateData['previous_progress'] ?? 'null',
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);

        $previousProgress = $event->updateData['previous_progress'] ?? null;
        $newProgress = $event->updateData['progress'];

        if ($previousProgress !== null && $previousProgress == (float) $newProgress) {
            Log::info('Skipping snapshot - progress unchanged');
            return;
        }

        ProjectScheduleProgressSnapshot::create([
            'project_id' => $event->project->id,
            'schedule_id' => $event->schedule->id,
            'progress' => $newProgress,
            'completed_quantity' => $event->updateData['completed_quantity'] ?? null,
            'target_quantity' => $event->updateData['target_quantity'] ?? null,
            'unit' => $event->updateData['unit'] ?? null,
            'snapshot_type' => 'manual',
            'remarks' => $event->updateData['remarks'] ?? null,
            'recorded_by' => Auth::id(),
            'snapshot_date' => now(),
        ]);

        Log::info('Snapshot created');
    }
}
