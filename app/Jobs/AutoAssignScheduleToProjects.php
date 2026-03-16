<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ProjectActivitySchedule;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoAssignScheduleToProjects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ProjectActivitySchedule $schedule;
    protected ?array $projectIds;

    /**
     * Create a new job instance.
     *
     * @param ProjectActivitySchedule
     * @param array|null
     */
    public function __construct(ProjectActivitySchedule $schedule, ?array $projectIds = null)
    {
        $this->schedule = $schedule;
        $this->projectIds = $projectIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $projects = $this->projectIds
                ? Project::whereIn('id', $this->projectIds)->get()
                : Project::where('status', 'active')->get();

            $assignedCount = 0;
            $skippedCount = 0;

            foreach ($projects as $project) {
                if ($project->activitySchedules()->where('schedule_id', $this->schedule->id)->exists()) {
                    $skippedCount++;
                    continue;
                }

                DB::table('project_schedule_assignments')->insert([
                    'project_id' => $project->id,
                    'schedule_id' => $this->schedule->id,
                    'progress' => 0,
                    'status' => 'active',
                    'start_date' => null,
                    'end_date' => null,
                    'actual_start_date' => null,
                    'actual_end_date' => null,
                    'remarks' => 'Auto-assigned by system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('schedule_auto_assignments')->insert([
                    'schedule_id' => $this->schedule->id,
                    'project_id' => $project->id,
                    'assigned_at' => now(),
                    'assigned_by' => 'system',
                    'notes' => "Auto-assigned activity {$this->schedule->code} to project {$project->title}",
                ]);

                $assignedCount++;
            }

            Log::info("Auto-assignment completed for {$this->schedule->code}: {$assignedCount} assigned, {$skippedCount} skipped");
        } catch (\Exception $e) {
            Log::error("Auto-assignment failed for {$this->schedule->code}: " . $e->getMessage());
            throw $e;
        }
    }
}
