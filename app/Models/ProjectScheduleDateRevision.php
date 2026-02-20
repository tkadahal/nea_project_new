<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectScheduleDateRevision extends Model
{
    protected $fillable = [
        'project_id',
        'schedule_id',
        'actual_start_date',
        'actual_end_date',
        'revision_reason',
        'remarks',
        'revised_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ProjectActivitySchedule::class, 'schedule_id');
    }

    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
