<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ProjectActivityDefinition;
use App\Exports\Templates\ProjectActivityTemplateExport;
use App\Exceptions\StructuralChangeRequiresConfirmationException;

class ProjectActivityExcelService
{
    public function processUpload($file, bool $force = false): void
    {
        DB::transaction(function () use ($file, $force) {

            $spreadsheet = IOFactory::load($file->getRealPath());

            // ✅ NEW: Read from Instructions sheet
            [$projectId, $fiscalYearId] = $this->extractMetadata($spreadsheet);

            $this->validateUserAccess($projectId);

            $capitalSheet   = $spreadsheet->getSheetByName('पूँजीगत खर्च');
            $recurrentSheet = $spreadsheet->getSheetByName('चालू खर्च');

            if (!$capitalSheet) {
                throw new Exception('Sheet "पूँजीगत खर्च" not found');
            }

            $capitalData   = $this->parseSheet($capitalSheet, 1);
            $recurrentData = $recurrentSheet ? $this->parseSheet($recurrentSheet, 2) : [];

            // ────────────────────────────────────────────────
            // VALIDATION: Prevent completely useless / blank / unmodified-template uploads
            // ────────────────────────────────────────────────
            $hasMeaningfulActivity = false;

            $allRows = array_merge($capitalData, $recurrentData);

            foreach ($allRows as $row) {
                $program = trim($row['program'] ?? '');

                if (strlen($program) < 3) {
                    continue;
                }

                // Expanded list of invalid / placeholder / total texts
                if (preg_match(
                    '/^(total|जम्मा|subtotal|कुल|sum|heading|शीर्षक|कुल जम्मा|grand total|जम्मा रकम|कुल लागत|sub-total|total budget|आंशिक जम्मा|अन्तिम जम्मा|योग|सब टोटल|partial total|' .
                        'मुख्य कार्यक्रम उदाहरण|उप कार्यक्रम उदाहरण|कार्यक्रम उदाहरण|क्रियाकलाप उदाहरण|उदाहरण|example|demo|placeholder|test activity)$/ui',
                    $program
                )) {
                    continue;
                }

                $hasPlanning = (
                    ($row['planned_budget']   ?? 0) > 0 ||
                    ($row['planned_quantity'] ?? 0) > 0 ||
                    ($row['q1_amount']        ?? 0) > 0 ||
                    ($row['q1_quantity']      ?? 0) > 0 ||
                    ($row['q2_amount']        ?? 0) > 0 ||
                    ($row['q2_quantity']      ?? 0) > 0 ||
                    ($row['q3_amount']        ?? 0) > 0 ||
                    ($row['q3_quantity']      ?? 0) > 0 ||
                    ($row['q4_amount']        ?? 0) > 0 ||
                    ($row['q4_quantity']      ?? 0) > 0
                );

                $hasProgress = (
                    ($row['completed_quantity'] ?? 0) > 0 ||
                    ($row['total_expense']      ?? 0) > 0
                );

                if ($hasPlanning || $hasProgress) {
                    $hasMeaningfulActivity = true;
                    break;
                }
            }

            if (!$hasMeaningfulActivity) {
                throw new Exception(
                    'कुनै पनि वास्तविक क्रियाकलापमा उपयोगी जानकारी भेटिएन । ' .
                        'टेम्प्लेटमा रहेका उदाहरणहरू (जस्तै: मुख्य कार्यक्रम उदाहरण) हटाई, वास्तविक क्रियाकलापको नाम, योजना वा प्रगति विवरण भर्नुहोस् ।'
                );
            }

            $isPureFiscalYearChange = false;

            $this->checkPlanEditableOrStructuralChange(
                $projectId,
                $fiscalYearId,
                count($capitalData),
                count($recurrentData),
                $force,
                $isPureFiscalYearChange
            );

            /**
             * ======================================================
             * CASE 1: PURE FISCAL YEAR CHANGE (same structure)
             * ======================================================
             */

            if ($isPureFiscalYearChange) {
                // Check if current definitions are in draft mode
                $anyPlanInDraft = ProjectActivityPlan::join(
                    'project_activity_definitions as def',
                    'project_activity_plans.activity_definition_version_id',
                    '=',
                    'def.id'
                )
                    ->where('def.project_id', $projectId)
                    ->where('def.is_current', true)
                    ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
                    ->where('project_activity_plans.status', 'draft')
                    ->exists();

                $capitalDefIds = $this->updateDefinitionsIfDraft($projectId, 1, $capitalData, $anyPlanInDraft);
                $recurrentDefIds = $this->updateDefinitionsIfDraft($projectId, 2, $recurrentData, $anyPlanInDraft);

                $this->syncPlans($fiscalYearId, $capitalData, $capitalDefIds);
                $this->syncPlans($fiscalYearId, $recurrentData, $recurrentDefIds);

                return;
            }

            /**
             * ======================================================
             * CASE 2: STRUCTURAL CHANGE (new version)
             * ======================================================
             */
            $currentProjectVersion = $this->getCurrentProjectVersion($projectId);
            $newProjectVersion = $currentProjectVersion + 1;
            $previousVersion = $force ? $currentProjectVersion : null;

            if ($force) {
                ProjectActivityDefinition::where('project_id', $projectId)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
                    ->whereIn(
                        'activity_definition_version_id',
                        ProjectActivityDefinition::where('project_id', $projectId)->pluck('id')
                    )
                    ->delete();
            }

            $capitalDefIds = $this->syncDefinitions($projectId, 1, $capitalData, $force, $newProjectVersion, $previousVersion);
            $recurrentDefIds = $this->syncDefinitions($projectId, 2, $recurrentData, $force, $newProjectVersion, $previousVersion);

            $this->syncPlans($fiscalYearId, $capitalData, $capitalDefIds);
            $this->syncPlans($fiscalYearId, $recurrentData, $recurrentDefIds);
        });
    }

    /**
     * ======================================================
     * STRUCTURE / VERSION CHECK
     * ======================================================
     */
    private function checkPlanEditableOrStructuralChange(
        int $projectId,
        int $fiscalYearId,
        int $capitalRowCount,
        int $recurrentRowCount,
        bool $force,
        bool &$isPureFiscalYearChange = false
    ): void {
        $counts = ProjectActivityTemplateExport::getCurrentDefinitionCounts(Project::find($projectId));

        $currentCapitalCount = $counts['capital'];
        $currentRecurrentCount = $counts['recurrent'];

        $hasExistingData = ($currentCapitalCount + $currentRecurrentCount) > 0;
        $structureMatches = ($currentCapitalCount === $capitalRowCount) && ($currentRecurrentCount === $recurrentRowCount);

        $plansExistForThisFY = ProjectActivityPlan::join(
            'project_activity_definitions as def',
            'project_activity_plans.activity_definition_version_id',
            '=',
            'def.id'
        )
            ->where('def.project_id', $projectId)
            ->where('def.is_current', true)
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->exists();

        if ($structureMatches && $hasExistingData) {
            if ($plansExistForThisFY) {
                // Check if plans are in draft mode - if so, allow edit
                $allPlansInDraft = ProjectActivityPlan::join(
                    'project_activity_definitions as def',
                    'project_activity_plans.activity_definition_version_id',
                    '=',
                    'def.id'
                )
                    ->where('def.project_id', $projectId)
                    ->where('def.is_current', true)
                    ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
                    ->where('project_activity_plans.status', '!=', 'draft')
                    ->doesntExist();

                if (!$allPlansInDraft) {
                    throw new Exception('Plans for this fiscal year already exist and are not in draft mode. Cannot edit.');
                }
            }

            $isPureFiscalYearChange = true;
            return;
        }

        if (!$force && $hasExistingData) {
            throw new StructuralChangeRequiresConfirmationException();
        }

        $isPureFiscalYearChange = false;
    }

    /**
     * ======================================================
     * UPDATE DEFINITIONS IF IN DRAFT MODE
     * ======================================================
     */
    private function updateDefinitionsIfDraft(int $projectId, int $expenditureId, array $data, bool $isDraft): array
    {
        if (empty($data)) {
            return [];
        }

        $hashToId = [];

        foreach ($data as $hash => $row) {
            $existing = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('sort_index', $hash)
                ->where('is_current', true)
                ->first();

            if (!$existing) {
                throw new Exception("Definition not found for hash: {$hash}");
            }

            // Update definition fields if in draft mode
            if ($isDraft) {
                $parentId = $row['parent_hash'] && isset($hashToId[$row['parent_hash']])
                    ? $hashToId[$row['parent_hash']]
                    : null;

                $existing->update([
                    'parent_id' => $parentId,
                    'depth' => $row['depth'],
                    'program' => $row['program'],
                    'total_quantity' => $row['total_quantity'],
                    'total_budget' => $row['total_budget'],
                ]);
            }

            $hashToId[$hash] = $existing->id;
        }

        return $hashToId;
    }

    /**
     * ======================================================
     * PARSE EXPENDITURE SHEET
     * ======================================================
     */
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

    /**
     * ======================================================
     * SYNC DEFINITIONS (NEW VERSION)
     * ======================================================
     */
    private function syncDefinitions(int $projectId, int $expenditureId, array $data, bool $force, int $newVersion, ?int $previousVersion = null): array
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
                'previous_version_id' => $previousVersion,
                'is_current' => true,
                'versioned_at' => now(),
            ];

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

    /**
     * ======================================================
     * SYNC PLANS
     * ======================================================
     */
    private function syncPlans(int $fiscalYearId, array $data, array $hashToIdMap): void
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $hash => $row) {
            $definitionId = $hashToIdMap[$hash] ?? null;
            if (!$definitionId) {
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

    private function validateUserAccess(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        if (!$project->users->contains(Auth::id())) {
            throw new Exception('You do not have access to this project');
        }
    }

    /**
     * ======================================================
     * READ METADATA FROM INSTRUCTIONS SHEET
     * ======================================================
     */
    private function extractMetadata($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Instructions')
            ?? $spreadsheet->getSheet(0);

        if (!$sheet) {
            throw new Exception('Instructions sheet not found');
        }

        $projectId = (int) $sheet->getCell('B1')->getValue();
        $fiscalYearId = (int) $sheet->getCell('B2')->getValue();

        if (empty($projectId) || empty($fiscalYearId)) {
            throw new Exception('Invalid File Format: Project ID or Fiscal Year ID is missing. Please download a fresh template.');
        }

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        return [$project->id, $fiscalYear->id];
    }
}
