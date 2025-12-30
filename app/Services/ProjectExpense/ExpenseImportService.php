<?php

declare(strict_types=1);

namespace App\Services\ProjectExpense;

use App\Models\ProjectActivityPlan;
use App\DTOs\ProjectExpense\ExpenseImportResultDTO;
use App\Repositories\ProjectExpense\ProjectExpenseRepository;
use App\Helpers\ProjectExpense\ExcelQuarterExtractor;
use App\Helpers\ProjectExpense\ExcelRowParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportProjectExpense;

class ExpenseImportService
{
    public function __construct(
        private readonly ProjectExpenseRepository $repository,
        private readonly ExcelQuarterExtractor $quarterExtractor,
        private readonly ExcelRowParser $rowParser
    ) {}

    public function importFromExcel(
        UploadedFile $file,
        int $projectId,
        int $fiscalYearId
    ): ExpenseImportResultDTO {
        $quarterNumber = $this->quarterExtractor->extractQuarterFromExcel($file);

        if (!$quarterNumber) {
            throw new \InvalidArgumentException(
                'Could not detect quarter from file. Please use the latest template.'
            );
        }

        return DB::transaction(function () use ($file, $projectId, $fiscalYearId, $quarterNumber) {
            $rows = Excel::toCollection(
                new ImportProjectExpense($projectId, $fiscalYearId, $quarterNumber),
                $file
            )->first()->toArray();

            $processed = $this->rowParser->parseRows($rows, $projectId, $fiscalYearId);

            if (empty($processed)) {
                throw new \Exception('No valid activities found.');
            }

            $map = $this->processImportedActivities($processed, $projectId, $fiscalYearId, $quarterNumber);
            $this->updateParentRelationships($processed, $map);

            return new ExpenseImportResultDTO(
                quarterNumber: $quarterNumber,
                processedCount: count($processed),
                expenseIds: array_values($map)
            );
        });
    }

    private function processImportedActivities(
        array $processed,
        int $projectId,
        int $fiscalYearId,
        int $quarterNumber
    ): array {
        $userId = Auth::id();
        $map = [];

        foreach ($processed as $data) {
            $plan = ProjectActivityPlan::findOrFail($data['activity_id']);

            if (
                $plan->definitionVersion->project_id != $projectId ||
                $plan->fiscal_year_id != $fiscalYearId ||
                !$plan->definitionVersion->is_current
            ) {
                throw new \Exception('Activity mismatch.');
            }

            $expense = $this->repository->createOrUpdateExpense(
                $data['activity_id'],
                $userId
            );

            if ($data['qty'] > 0 || $data['amt'] > 0) {
                $this->repository->updateOrCreateQuarter(
                    $expense,
                    $quarterNumber,
                    $data['qty'],
                    $data['amt']
                );
            } else {
                $this->repository->deleteQuarter($expense, $quarterNumber);
            }

            $map[$data['activity_id']] = $expense->id;
        }

        return $map;
    }

    private function updateParentRelationships(array $processed, array $map): void
    {
        foreach ($processed as $data) {
            if ($data['parent_activity_id'] && ($parentId = $map[$data['parent_activity_id']] ?? null)) {
                $this->repository->updateParentId($map[$data['activity_id']], $parentId);
            }
        }
    }
}
