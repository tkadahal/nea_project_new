<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Contract;
use App\Models\ContractActivitySchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleProgressUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ContractActivitySchedule $schedule,
        public array $updateData
    ) {}
}
