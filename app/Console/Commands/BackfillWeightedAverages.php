<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectActivity;
use App\Models\ProjectExpense;

class BackfillWeightedAverages extends Command
{
    protected $signature = 'project:backfill-weights';
    protected $description = 'Compute weighted progress for existing records';

    public function handle()
    {
        $this->info('Computing for activities...');
        ProjectActivity::with('expenses.quarters')->chunk(100, function ($activities) {
            foreach ($activities as $activity) {
                // Add method_exists check for safety (in case of errors)
                if (method_exists($activity, 'computeAndSaveWeightedProgress')) {
                    $activity->computeAndSaveWeightedProgress();
                }
                $activity->saveQuietly();
            }
            $this->info('Processed 100 activities...');
        });

        $this->info('Computing for expenses...');
        ProjectExpense::with('quarters', 'projectActivity')->chunk(100, function ($expenses) {
            foreach ($expenses as $expense) {
                if (method_exists($expense, 'computeAndSaveWeightedProgress')) {
                    $expense->computeAndSaveWeightedProgress();
                }
                $expense->saveQuietly();
            }
            $this->info('Processed 100 expenses...');
        });

        $this->info('Backfill complete! Run `php artisan cache:clear` if needed.');
    }
}
