<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contract;

class ContractObserver
{
    public function created(Contract $contract): void
    {
        $contract->project->updatePhysicalProgress();
    }

    public function updated(Contract $contract): void
    {
        $contract->project->updatePhysicalProgress();
    }

    public function deleted(Contract $contract): void
    {
        $contract->project->updatePhysicalProgress();
    }
}
