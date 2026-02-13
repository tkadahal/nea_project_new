<?php

declare(strict_types=1);

namespace App\Services\Budget;

use App\Models\User;
use App\Models\Budget;
use App\Models\Project;
use App\Models\FiscalYear;
use App\DTOs\Budget\BudgetDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Budget\BudgetHelper;
use Illuminate\Support\Facades\Auth;
use App\DTOs\Budget\BudgetImportResult;
use App\Repositories\Budget\BudgetRepository;

class BudgetService
{
    public function __construct(
        private readonly BudgetRepository $budgetRepository
    ) {}

    public function getIndexData(?User $user = null): array
    {
        $budgets = $this->budgetRepository->getFilteredBudgets($user);

        $headers = [
            trans('global.budget.fields.id'),
            trans('global.budget.fields.fiscal_year_id'),
            trans('global.budget.fields.project_id'),
            trans('global.budget.fields.government_share'),
            trans('global.budget.fields.government_loan'),
            trans('global.budget.fields.foreign_loan_budget'),
            trans('global.budget.fields.foreign_subsidy_budget'),
            trans('global.budget.fields.internal_budget'),
            trans('global.budget.fields.total_budget'),
            trans('global.budget.fields.budget_revision'),
        ];


        $data = $budgets->map(fn($budget) => BudgetHelper::formatBudgetForDisplay($budget))->all();

        return [
            'headers' => $headers,
            'data' => $data,
            'budgets' => $budgets,
            'routePrefix' => 'admin.budget',
            'actions' => ['view', 'edit', 'delete', 'quarterly'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project budget?',
        ];
    }

    public function getFilteredBudgetsData(
        ?User $user = null,
        int $perPage = 20,
        ?int $directorateId = null,
        ?int $projectId = null,
        ?int $fiscalYearId = null,
        ?string $search = null
    ): array {
        $budgets = $this->budgetRepository->getFilteredBudgetsWithPagination(
            $user,
            $directorateId,
            $projectId,
            $fiscalYearId,
            $search,
            $perPage
        );

        $transformedData = $budgets->getCollection()->map(function ($budget) {
            return [
                'id' => $budget->id,
                'project_id' => $budget->project_id,
                'fiscal_year' => $budget->fiscalYear->title ?? 'N/A',
                'project' => $budget->project->title ?? 'N/A',
                'directorate' => $budget->project->directorate->title ?? 'N/A',
                'directorate_id' => $budget->project->directorate_id ?? null,
                'government_share' => number_format((float)($budget->government_share ?? 0), 2),
                'government_loan' => number_format((float)($budget->government_loan ?? 0), 2),
                'foreign_loan' => number_format((float)($budget->foreign_loan_budget ?? 0), 2),
                'foreign_subsidy' => number_format((float)($budget->foreign_subsidy_budget ?? 0), 2),
                'internal_budget' => number_format((float)($budget->internal_budget ?? 0), 2),
                'total_budget' => number_format((float)($budget->total_budget ?? 0), 2),
                'budget_revision' => $budget->budget_revision,
            ];
        })->values()->toArray();

        return [
            'data' => $transformedData,
            'current_page' => $budgets->currentPage(),
            'last_page' => $budgets->lastPage(),
            'per_page' => $budgets->perPage(),
            'total' => $budgets->total(),
        ];
    }

    /**
     * Get lightweight list of projects for dropdown
     */
    public function getProjectsForDropdown(): array
    {
        $projects = $this->budgetRepository->getProjectsForUser(
            Auth::user(),
            withRelations: false,
            paginate: null
        )->sortByDesc('id');

        return $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate_id' => $project->directorate_id,
            ];
        })->toArray();
    }

    public function getFiltersData(?User $user = null): array
    {
        return [
            'directorates' => $this->budgetRepository->getAccessibleDirectorates($user),
            'projects' => $this->budgetRepository->getAccessibleProjects($user)->sortByDesc('id'),
            'fiscalYears' => $this->budgetRepository->getAccessibleFiscalYears(),
        ];
    }

    // public function getProjectsForDropdown(?User $user = null, ?int $directorateId = null)
    // {
    //     return $this->budgetRepository->getAccessibleProjects($user, $directorateId);
    // }

    public function getDirectorateTitle(?User $user = null): string
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return 'All Directorates';
        }

        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(\App\Models\Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            return $user->directorate->title ?? 'Unknown Directorate';
        }

        return 'All Directorates';
    }

    public function getDirectoratesForFilter(?User $user = null, ?int $selectedDirectorateId = null): array
    {
        $user = $user ?? Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(\App\Models\Role::DIRECTORATE_USER, $roleIds) && $user->directorate_id) {
            return [];
        }

        return $this->budgetRepository->getAccessibleDirectorates($user)
            ->map(function ($d) use ($selectedDirectorateId) {
                return [
                    'value' => $d->id,
                    'label' => $d->title,
                    'selected' => $selectedDirectorateId == $d->id,
                ];
            })->prepend([
                'value' => '',
                'label' => trans('global.allDirectorate') ?? 'All Directorates',
                'selected' => !$selectedDirectorateId,
            ])
            ->sortByDesc('id')
            ->values()
            ->toArray();
    }

    public function createOrUpdateBudgets(array $validatedData): BudgetImportResult
    {
        $fiscalYearId = $validatedData['fiscal_year_id'];
        $projectIds = $validatedData['project_id'] ?? [];

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($projectIds as $projectId) {
            $budgetData = BudgetDTO::fromArray([
                'fiscal_year_id' => $fiscalYearId,
                'project_id' => $projectId,
                'internal_budget' => $validatedData['internal_budget'][$projectId] ?? 0,
                'government_share' => $validatedData['government_share'][$projectId] ?? 0,
                'government_loan' => $validatedData['government_loan'][$projectId] ?? 0,
                'foreign_loan_budget' => $validatedData['foreign_loan_budget'][$projectId] ?? 0,
                'foreign_loan_source' => $validatedData['foreign_loan_source'][$projectId] ?? null,
                'foreign_subsidy_budget' => $validatedData['foreign_subsidy_budget'][$projectId] ?? 0,
                'foreign_subsidy_source' => $validatedData['foreign_subsidy_source'][$projectId] ?? null,
                'total_budget' => $validatedData['total_budget'][$projectId] ?? 0,
                'decision_date' => $validatedData['decision_date'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
            ]);

            if (!$budgetData->hasNonZeroBudget()) {
                $skipped++;
                continue;
            }

            $project = Project::find($projectId);
            if (!$project) {
                $errors[] = "Project ID {$projectId} not found.";
                continue;
            }

            $existingBudget = $this->budgetRepository->findByProjectAndFiscalYear(
                $projectId,
                $fiscalYearId
            );

            if ($existingBudget) {
                $this->budgetRepository->update($existingBudget, $budgetData);
                $this->budgetRepository->createRevision($existingBudget->fresh(), $budgetData);
                $updated++;
            } else {
                $budget = $this->budgetRepository->create($budgetData);
                $this->budgetRepository->createRevision($budget, $budgetData);
                $created++;
            }
        }

        return new BudgetImportResult($created, $updated, $skipped, $errors);
    }

    public function importFromExcel(\Illuminate\Http\UploadedFile $file): BudgetImportResult
    {
        try {
            if (!$file->isValid()) {
                return new BudgetImportResult(
                    errors: ['File upload failed: ' . $file->getErrorMessage()]
                );
            }

            // Extract fiscal year from template
            $fiscalYearTitle = $this->extractFiscalYearFromExcel($file);

            if (!$fiscalYearTitle) {
                return new BudgetImportResult(
                    errors: ['Could not extract fiscal year from the template. Expected format: "Fiscal Year: YYYY/YY" in row 2.']
                );
            }

            Log::info('Extracted fiscal year from template', ['fiscal_year' => $fiscalYearTitle]);

            $fiscalYear = FiscalYear::where('title', $fiscalYearTitle)->first();

            if (!$fiscalYear) {
                return new BudgetImportResult(
                    errors: ["Fiscal year '{$fiscalYearTitle}' not found in the system. Please ensure it exists before uploading."]
                );
            }

            // Import data
            $import = new \App\Imports\BudgetImport();
            $data = $import->import($file);

            if ($data->isEmpty()) {
                return new BudgetImportResult(
                    errors: ['No valid data found in the Excel file.']
                );
            }

            // Process imported data
            return $this->processBudgetDTO($data, $fiscalYear->id, $fiscalYearTitle);
        } catch (\Exception $e) {
            Log::error('Budget import error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new BudgetImportResult(
                errors: ['Error: ' . $e->getMessage()]
            );
        }
    }

    private function extractFiscalYearFromExcel(\Illuminate\Http\UploadedFile $file): ?string
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $fiscalYearRow = $worksheet->getCell('A2')->getValue();

        return BudgetHelper::extractFiscalYear($fiscalYearRow);
    }

    private function processBudgetDTO($data, int $fiscalYearId, string $fiscalYearTitle): BudgetImportResult
    {
        $projects = BudgetHelper::getNormalizedProjectMap();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $projectTitle = trim($row['project_title'] ?? '');

            if (empty($projectTitle)) {
                $errors[] = "Missing project title at row " . ($index + 4);
                continue;
            }

            $normalizedTitle = BudgetHelper::normalizeString($projectTitle);
            $projectId = $projects[$normalizedTitle] ?? null;

            if (!$projectId) {
                $errors[] = "Invalid project '{$projectTitle}' at row " . ($index + 4);
                continue;
            }

            $budgetData = BudgetDTO::fromArray([
                'fiscal_year_id' => $fiscalYearId,
                'project_id' => $projectId,
                'government_loan' => floatval($row['government_loan'] ?? 0),
                'government_share' => floatval($row['government_share'] ?? 0),
                'foreign_loan_budget' => floatval($row['foreign_loan_budget'] ?? 0),
                'foreign_loan_source' => trim($row['foreign_loan_source'] ?? ''),
                'foreign_subsidy_budget' => floatval($row['foreign_subsidy_budget'] ?? 0),
                'foreign_subsidy_source' => trim($row['foreign_subsidy_source'] ?? ''),
                'internal_budget' => floatval($row['internal_budget'] ?? 0),
                'total_budget' => floatval($row['total_budget'] ?? 0),
            ]);

            if (!$budgetData->hasNonZeroBudget()) {
                $skipped++;
                continue;
            }

            $existingBudget = $this->budgetRepository->findByProjectAndFiscalYear(
                $projectId,
                $fiscalYearId
            );

            if ($existingBudget) {
                $this->budgetRepository->update($existingBudget, $budgetData);
                $this->budgetRepository->createRevision($existingBudget->fresh(), $budgetData);
                $updated++;
            } else {
                $budget = $this->budgetRepository->create($budgetData);
                $this->budgetRepository->createRevision($budget, $budgetData);
                $created++;
            }
        }

        if (!empty($errors)) {
            return new BudgetImportResult(0, 0, 0, $errors);
        }

        return new BudgetImportResult($created, $updated, $skipped, [], $fiscalYearTitle);
    }

    public function cleanAllDuplicates(): array
    {
        DB::beginTransaction();

        try {
            $budgets = $this->budgetRepository->getBudgetsWithMultipleRevisions();
            $totalDeleted = 0;

            foreach ($budgets as $budget) {
                $deleted = $this->budgetRepository->cleanDuplicateRevisions($budget);
                $totalDeleted += $deleted;
            }

            DB::commit();

            return [
                'success' => true,
                'deleted' => $totalDeleted,
                'message' => "✅ Cleaned {$totalDeleted} duplicate revision(s) and synced latest data."
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => '❌ Cleanup failed: ' . $e->getMessage()
            ];
        }
    }
}
