<?php

declare(strict_types=1);

namespace App\Services\ProjectExpense;

use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\DTO\ProjectExpense\ActivityExpenseDTO;
use App\Helpers\ProjectExpense\ExcelImportHelper;
use App\Repositories\ProjectExpense\ProjectExpenseRepository;

class ProjectExpenseService
{
    public function __construct(
        private ProjectExpenseRepository $repo,
        private ExcelImportHelper $excelHelper
    ) {}

    public function getAggregatedExpenses()
    {
        return $this->repo->getAggregatedExpenses();
    }

    public function prepareCreateData(array $input): array
    {
        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $input['project_id'] ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();
        $selectedFiscalYearId = $input['fiscal_year_id'] ?? null;

        $projectOptions = $projects->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->title,
            'selected' => $p->id === $selectedProjectId,
        ])->toArray();

        $selectedQuarter = $input['selected_quarter'] ?? null;
        if (!$selectedQuarter && $selectedProjectId && $selectedFiscalYearId) {
            $selectedQuarter = $this->repo->getNextUnfilledQuarter($selectedProjectId, $selectedFiscalYearId);
        }

        $preloadActivities = !empty($selectedProjectId) && !empty($selectedFiscalYearId);
        $quarterStatus = ($selectedProjectId && $selectedFiscalYearId)
            ? $this->repo->getQuarterCompletionStatus($selectedProjectId, $selectedFiscalYearId)
            : null;

        return compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'selectedQuarter',
            'quarterStatus',
            'preloadActivities'
        );
    }

    public function getNextQuarterWithStatus(int $projectId, int $fiscalYearId): array
    {
        return [
            'quarter' => $this->repo->getNextUnfilledQuarter($projectId, $fiscalYearId),
            'quarterStatus' => $this->repo->getQuarterCompletionStatus($projectId, $fiscalYearId),
        ];
    }

    public function storeExpenses(int $projectId, int $fiscalYearId, int $quarter, array $validated): void
    {
        DB::transaction(function () use ($projectId, $fiscalYearId, $quarter, $validated) {
            $activities = [];

            foreach (['capital', 'recurrent'] as $section) {
                if (!isset($validated[$section])) continue;

                foreach ($validated[$section] as $data) {
                    $activities[] = new ActivityExpenseDTO(
                        activityId: $data['activity_id'],
                        parentActivityId: $data['parent_id'] ?? null,
                        quantity: $data["q{$quarter}_qty"] ?? 0,
                        amount: $data["q{$quarter}_amt"] ?? 0,
                        description: $data['description'] ?? null
                    );
                }
            }

            if (empty($activities)) {
                throw new \InvalidArgumentException('No activity data submitted.');
            }

            $this->repo->saveExpenseBatch($activities, $projectId, $fiscalYearId, $quarter);
        });
    }

    public function getShowViewData(int $projectId, int $fiscalYearId): array
    {
        return $this->repo->getShowViewData($projectId, $fiscalYearId);
    }

    public function getExpenseTreeForAjax(int $projectId, int $fiscalYearId): array
    {
        return $this->repo->buildExpenseTreeForAjax($projectId, $fiscalYearId);
    }

    public function generateTemplateFileName(int $projectId, int $fiscalYearId, string $quarterLabel): string
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $safeProject = str_replace(['/', '\\'], '_', Str::slug($project->title));
        $safeFiscal = str_replace(['/', '\\'], '_', $fiscalYear->title);
        return "Expense_Template_{$safeProject}_{$safeFiscal}_{$quarterLabel}.xlsx";
    }

    public function generateReportFileName(int $projectId, int $fiscalYearId): string
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $safeProject = str_replace(['/', '\\'], '_', Str::slug($project->title));
        $safeFiscal = str_replace(['/', '\\'], '_', $fiscalYear->title);
        return "ExpenseReport_{$safeProject}_{$safeFiscal}.xlsx";
    }

    public function getUploadViewData(int $projectId, int $fiscalYearId, string $quarter): array
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        if (!in_array($quarter, ['q1', 'q2', 'q3', 'q4'])) {
            abort(400, 'Invalid quarter');
        }

        return compact('project', 'fiscalYear', 'quarter');
    }

    public function processUpload($file, int $projectId, int $fiscalYearId): int
    {
        $quarterNumber = $this->excelHelper->extractQuarter($file);
        if (!$quarterNumber) {
            throw new \Exception('Could not detect quarter from file.');
        }

        $rows = Excel::toCollection([], $file)->first()->toArray();

        $activities = $this->excelHelper->parseRowsToActivities($rows, $projectId, $fiscalYearId);

        if (empty($activities)) {
            throw new \Exception('No valid activities found in file.');
        }

        $this->repo->saveExpenseBatch($activities, $projectId, $fiscalYearId, $quarterNumber);

        return count($activities);
    }

    public function extractQuarterFromFile($file): ?int
    {
        return $this->excelHelper->extractQuarter($file);
    }
}
