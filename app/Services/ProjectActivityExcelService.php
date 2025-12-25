<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;
use App\Exports\Templates\ProjectActivityTemplateExport;
use App\Exceptions\StructuralChangeRequiresConfirmationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProjectActivityExcelService
{
    public function processUpload($file, bool $force = false): void
    {
        DB::transaction(function () use ($file, $force) {
            $spreadsheet = IOFactory::load($file->getRealPath());

            [$projectId, $fiscalYearId] = $this->extractMetadata($spreadsheet);
            $this->validateUserAccess($projectId);

            Log::info("Excel Upload: Project {$projectId}, FY {$fiscalYearId}, Force: " . ($force ? 'Yes' : 'No'));

            $capitalSheet = $spreadsheet->getSheetByName('पूँजीगत खर्च');
            $recurrentSheet = $spreadsheet->getSheetByName('चालू खर्च');

            if (!$capitalSheet) {
                throw new Exception('Capital sheet "पूँजीगत खर्च" not found');
            }

            $capitalData = $this->parseSheet($capitalSheet, 1);
            $recurrentData = $recurrentSheet ? $this->parseSheet($recurrentSheet, 2) : [];

            Log::info("Parsed data", [
                'capital_rows' => count($capitalData),
                'recurrent_rows' => count($recurrentData)
            ]);

            $this->checkPlanEditableOrStructuralChange(
                $projectId,
                $fiscalYearId,
                count($capitalData),
                count($recurrentData),
                $force
            );

            // Get project-wide version
            $currentProjectVersion = $this->getCurrentProjectVersion($projectId);
            $newProjectVersion = $currentProjectVersion + 1;
            $previousVersion = $force ? $currentProjectVersion : null;

            // On structural change: close ALL current definitions (both types)
            if ($force) {
                $closed = ProjectActivityDefinition::where('project_id', $projectId)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                Log::info("Structural change confirmed: closed {$closed} definitions (version {$currentProjectVersion}) for project {$projectId}");
            }

            $capitalDefIds = $this->syncDefinitions($projectId, 1, $capitalData, $force, $newProjectVersion, $previousVersion);
            $recurrentDefIds = $this->syncDefinitions($projectId, 2, $recurrentData, $force, $newProjectVersion, $previousVersion);

            Log::info("Synced definitions (version {$newProjectVersion})", [
                'capital_defs' => count($capitalDefIds),
                'recurrent_defs' => count($recurrentDefIds)
            ]);

            $this->syncPlans($fiscalYearId, $capitalData, $capitalDefIds);
            $this->syncPlans($fiscalYearId, $recurrentData, $recurrentDefIds);

            Log::info("Excel upload completed successfully (version {$newProjectVersion})");
        });
    }

    private function checkPlanEditableOrStructuralChange(
        int $projectId,
        int $fiscalYearId,
        int $capitalRowCount,
        int $recurrentRowCount,
        bool $force
    ): void {
        $counts = ProjectActivityTemplateExport::getCurrentDefinitionCounts(Project::find($projectId));

        $currentCapitalCount = $counts['capital'];
        $currentRecurrentCount = $counts['recurrent'];

        $hasExistingData = ($currentCapitalCount + $currentRecurrentCount) > 0;
        $structureMatches = ($currentCapitalCount === $capitalRowCount) && ($currentRecurrentCount === $recurrentRowCount);

        if ($structureMatches && $hasExistingData) {
            $existingPlan = ProjectActivityPlan::join(
                'project_activity_definitions',
                'project_activity_plans.activity_definition_version_id',
                '=',
                'project_activity_definitions.id'
            )
                ->where('project_activity_definitions.project_id', $projectId)
                ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
                ->where('project_activity_definitions.is_current', true)
                ->select('project_activity_plans.status')
                ->first();

            if ($existingPlan && $existingPlan->status !== 'draft') {
                throw new Exception('This annual program is already in review or approved mode so it cannot be edited.');
            }
            return;
        }

        if (!$force && $hasExistingData) {
            throw new StructuralChangeRequiresConfirmationException();
        }

        Log::info($hasExistingData ? "Structural change confirmed" : "First-time upload");
    }

    private function parseSheet($sheet, int $expenditureId, int $startRow = 5): array
    {
        $data = [];

        for ($rowNum = $startRow; $rowNum <= 200; $rowNum++) {
            $hash = trim((string) ($sheet->getCell('A' . $rowNum)->getCalculatedValue() ?? ''));

            if (empty($hash) || in_array(strtolower($hash), ['कुल जम्मा', 'total'])) {
                continue;
            }

            $cleanHash = str_replace('.', '', $hash);
            if (!is_numeric($cleanHash)) {
                continue;
            }

            $parts = explode('.', $hash);
            $depth = count($parts) - 1;
            $parentHash = $depth > 0 ? implode('.', array_slice($parts, 0, -1)) : null;

            $data[$hash] = [
                'hash' => $hash,
                'depth' => $depth,
                'parent_hash' => $parentHash,
                'expenditure_id' => $expenditureId,
                'program' => trim((string) ($sheet->getCell('B' . $rowNum)->getCalculatedValue() ?? '')),
                'total_quantity' => (float) ($sheet->getCell('C' . $rowNum)->getCalculatedValue() ?? 0),
                'total_budget' => (float) ($sheet->getCell('D' . $rowNum)->getCalculatedValue() ?? 0),
                'completed_quantity' => (float) ($sheet->getCell('E' . $rowNum)->getCalculatedValue() ?? 0),
                'total_expense' => (float) ($sheet->getCell('F' . $rowNum)->getCalculatedValue() ?? 0),
                'planned_quantity' => (float) ($sheet->getCell('G' . $rowNum)->getCalculatedValue() ?? 0),
                'planned_budget' => (float) ($sheet->getCell('H' . $rowNum)->getCalculatedValue() ?? 0),
                'q1_quantity' => (float) ($sheet->getCell('I' . $rowNum)->getCalculatedValue() ?? 0),
                'q1_amount' => (float) ($sheet->getCell('J' . $rowNum)->getCalculatedValue() ?? 0),
                'q2_quantity' => (float) ($sheet->getCell('K' . $rowNum)->getCalculatedValue() ?? 0),
                'q2_amount' => (float) ($sheet->getCell('L' . $rowNum)->getCalculatedValue() ?? 0),
                'q3_quantity' => (float) ($sheet->getCell('M' . $rowNum)->getCalculatedValue() ?? 0),
                'q3_amount' => (float) ($sheet->getCell('N' . $rowNum)->getCalculatedValue() ?? 0),
                'q4_quantity' => (float) ($sheet->getCell('O' . $rowNum)->getCalculatedValue() ?? 0),
                'q4_amount' => (float) ($sheet->getCell('P' . $rowNum)->getCalculatedValue() ?? 0),
            ];
        }

        uksort($data, 'strnatcmp');

        return $data;
    }

    private function syncDefinitions(int $projectId, int $expenditureId, array $data, bool $force, int $newVersion, int $previousVersion = null): array
    {
        if (empty($data)) {
            return [];
        }

        $hashToId = [];

        foreach ($data as $hash => $row) {
            $parentId = $row['parent_hash'] && isset($hashToId[$row['parent_hash']]) ? $hashToId[$row['parent_hash']] : null;

            $defData = [
                'project_id' => $projectId,
                'expenditure_id' => $expenditureId,
                'parent_id' => $parentId,
                'sort_index' => $hash,
                'depth' => $row['depth'],
                'program' => $row['program'],
                'total_quantity' => $row['total_quantity'],
                'total_budget' => $row['total_budget'],
                'version' => $newVersion,
                'previous_version_id' => $previousVersion, // Same for ALL rows
                'is_current' => true,
                'versioned_at' => now(),
            ];

            // Always create new on structural change or first time
            // On minor edit: update if exists
            if (!$force) {
                $existing = ProjectActivityDefinition::where('project_id', $projectId)
                    ->where('expenditure_id', $expenditureId)
                    ->where('sort_index', $hash)
                    ->where('is_current', true)
                    ->first();

                if ($existing) {
                    $existing->update($defData);
                    $realId = $existing->id;
                } else {
                    $newDef = ProjectActivityDefinition::create($defData);
                    $realId = $newDef->id;
                }
            } else {
                $newDef = ProjectActivityDefinition::create($defData);
                $realId = $newDef->id;
            }

            $hashToId[$hash] = $realId;
        }

        return $hashToId;
    }

    private function syncPlans(int $fiscalYearId, array $data, array $hashToIdMap): void
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $hash => $row) {
            $definitionId = $hashToIdMap[$hash] ?? null;
            if (!$definitionId) {
                Log::warning("Skipping plan - no definition for hash: {$hash}");
                continue;
            }

            $qAmountSum = $row['q1_amount'] + $row['q2_amount'] + $row['q3_amount'] + $row['q4_amount'];
            $qQuantitySum = $row['q1_quantity'] + $row['q2_quantity'] + $row['q3_quantity'] + $row['q4_quantity'];

            if (abs($qAmountSum - $row['planned_budget']) > 0.01) {
                throw new Exception("Row {$hash}: Quarterly amounts do not sum to planned budget");
            }

            if (abs($qQuantitySum - $row['planned_quantity']) > 0.01) {
                throw new Exception("Row {$hash}: Quarterly quantities do not sum to planned quantity");
            }

            $planData = [
                'activity_definition_version_id' => $definitionId,
                'fiscal_year_id' => $fiscalYearId,
                'planned_quantity' => $row['planned_quantity'],
                'planned_budget' => $row['planned_budget'],
                'q1_quantity' => $row['q1_quantity'],
                'q1_amount' => $row['q1_amount'],
                'q2_quantity' => $row['q2_quantity'],
                'q2_amount' => $row['q2_amount'],
                'q3_quantity' => $row['q3_quantity'],
                'q3_amount' => $row['q3_amount'],
                'q4_quantity' => $row['q4_quantity'],
                'q4_amount' => $row['q4_amount'],
                'completed_quantity' => $row['completed_quantity'],
                'total_expense' => $row['total_expense'],
                'status' => 'draft',
            ];

            ProjectActivityPlan::updateOrCreate(
                [
                    'activity_definition_version_id' => $definitionId,
                    'fiscal_year_id' => $fiscalYearId,
                ],
                $planData
            );
        }
    }

    private function getCurrentProjectVersion(int $projectId): int
    {
        $max = ProjectActivityDefinition::where('project_id', $projectId)->max('version');
        return $max ?? 0;
    }

    private function extractMetadata($spreadsheet): array
    {
        $capitalSheet = $spreadsheet->getSheetByName('पूँजीगत खर्च');

        $projectName = trim((string) $capitalSheet->getCell('A1')->getValue());
        $fiscalYearName = trim((string) $capitalSheet->getCell('H1')->getValue());

        if (empty($projectName)) {
            throw new Exception('Project title missing in cell A1');
        }

        if (empty($fiscalYearName)) {
            throw new Exception('Fiscal year missing in cell H1');
        }

        $project = Project::where('title', $projectName)->firstOrFail();
        $fiscalYear = FiscalYear::where('title', $fiscalYearName)->firstOrFail();

        return [$project->id, $fiscalYear->id];
    }

    private function validateUserAccess(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        if (!$project->users->contains(Auth::id())) {
            throw new Exception('You do not have access to this project');
        }
    }
}
