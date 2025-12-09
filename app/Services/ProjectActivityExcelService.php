<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;

class ProjectActivityExcelService
{
    public function __construct(
        private readonly ProjectActivityProcessor $processor
    ) {}

    public function processUpload($file): void
    {
        DB::transaction(function () use ($file) {
            $spreadsheet = IOFactory::load($file->getRealPath());

            [$projectId, $fiscalYearId] = $this->extractMetadata($spreadsheet);

            $this->validateUserAccess($projectId);

            $capitalData = $this->parseSheet($spreadsheet->getSheetByName('पूँजीगत खर्च'), 1);
            $this->validateAndInsert($capitalData, $projectId, $fiscalYearId);

            $recurrentSheet = $spreadsheet->getSheetByName('चालू खर्च');
            if ($recurrentSheet) {
                $recurrentData = $this->parseSheet($recurrentSheet, 2);
                $this->validateAndInsert($recurrentData, $projectId, $fiscalYearId);
            }
        });
    }

    private function extractMetadata($spreadsheet): array
    {
        $capitalSheet = $spreadsheet->getSheetByName('पूँजीगत खर्च');

        if (!$capitalSheet) {
            throw new Exception('Capital sheet "पूँजीगत खर्च" not found.');
        }

        $projectName = trim((string) $capitalSheet->getCell('A1')->getValue());
        $fiscalYearName = trim((string) $capitalSheet->getCell('H1')->getValue());

        if (empty($projectName)) {
            throw new Exception('Project title missing in cell A1.');
        }

        if (empty($fiscalYearName)) {
            throw new Exception('Fiscal year missing in cell H1.');
        }

        $project = Project::where('title', $projectName)->first();
        if (!$project) {
            throw new Exception("Project '{$projectName}' not found.");
        }

        $fiscalYear = FiscalYear::where('title', $fiscalYearName)->first();
        if (!$fiscalYear) {
            throw new Exception("Fiscal year '{$fiscalYearName}' not found.");
        }

        return [$project->id, $fiscalYear->id];
    }

    private function validateUserAccess(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        if (!$project->users->contains(Auth::id())) {
            throw new Exception('You do not have access to this project.');
        }
    }

    private function parseSheet($sheet, int $expenditureId, int $startRow = 5): array
    {
        if (!$sheet) {
            return [];
        }

        $data = [];
        $index = 0;

        for ($rowNum = $startRow; $rowNum <= 100; $rowNum++) {
            $hash = trim((string) ($sheet->getCell('A' . $rowNum)->getCalculatedValue() ?? ''));

            if (empty($hash) || $hash === 'कुल जम्मा') {
                continue;
            }

            $cleanHash = str_replace('.', '', $hash);
            if (!is_numeric($cleanHash)) {
                continue;
            }

            $parts = explode('.', $hash);
            $level = count($parts) - 1;
            $parentHash = $level > 0 ? implode('.', array_slice($parts, 0, -1)) : null;

            $data[] = [
                'index' => $index++,
                'hash' => $hash,
                'level' => $level,
                'parent_hash' => $parentHash,
                'program' => trim((string) ($sheet->getCell('B' . $rowNum)->getCalculatedValue() ?? '')),
                'total_budget' => (float) ($sheet->getCell('D' . $rowNum)->getCalculatedValue() ?? 0),
                'total_quantity' => (float) ($sheet->getCell('C' . $rowNum)->getCalculatedValue() ?? 0),
                'total_expense' => (float) ($sheet->getCell('F' . $rowNum)->getCalculatedValue() ?? 0),
                'completed_quantity' => (float) ($sheet->getCell('E' . $rowNum)->getCalculatedValue() ?? 0),
                'planned_budget' => (float) ($sheet->getCell('H' . $rowNum)->getCalculatedValue() ?? 0),
                'planned_quantity' => (float) ($sheet->getCell('G' . $rowNum)->getCalculatedValue() ?? 0),
                'q1' => (float) ($sheet->getCell('J' . $rowNum)->getCalculatedValue() ?? 0),
                'q1_quantity' => (float) ($sheet->getCell('I' . $rowNum)->getCalculatedValue() ?? 0),
                'q2' => (float) ($sheet->getCell('L' . $rowNum)->getCalculatedValue() ?? 0),
                'q2_quantity' => (float) ($sheet->getCell('K' . $rowNum)->getCalculatedValue() ?? 0),
                'q3' => (float) ($sheet->getCell('N' . $rowNum)->getCalculatedValue() ?? 0),
                'q3_quantity' => (float) ($sheet->getCell('M' . $rowNum)->getCalculatedValue() ?? 0),
                'q4' => (float) ($sheet->getCell('P' . $rowNum)->getCalculatedValue() ?? 0),
                'q4_quantity' => (float) ($sheet->getCell('O' . $rowNum)->getCalculatedValue() ?? 0),
                'expenditure_id' => $expenditureId,
            ];
        }

        usort($data, fn($a, $b) => strcmp($a['hash'], $b['hash']));

        return $data;
    }

    private function validateAndInsert(array $data, int $projectId, int $fiscalYearId): void
    {
        if (empty($data)) {
            return;
        }

        $this->validateExcelData($data);
        $this->insertHierarchicalData($data, $projectId, $fiscalYearId);
    }

    private function validateExcelData(array $data): void
    {
        $errors = [];
        $hashToChildren = [];

        foreach ($data as $row) {
            if ($row['parent_hash']) {
                $hashToChildren[$row['parent_hash']][] = $row;
            }
        }

        foreach ($data as $row) {
            // Validate quarter sums for amounts
            $quarterAmountSum = $row['q1'] + $row['q2'] + $row['q3'] + $row['q4'];
            if (abs($row['planned_budget'] - $quarterAmountSum) > 0.01) {
                $errors[] = "Row #{$row['hash']}: Quarter amounts don't match planned budget.";
            }

            // Validate quarter sums for quantities (ADDED)
            $quarterQuantitySum = $row['q1_quantity'] + $row['q2_quantity'] + $row['q3_quantity'] + $row['q4_quantity'];
            if (abs($row['planned_quantity'] - $quarterQuantitySum) > 0.01) {
                $errors[] = "Row #{$row['hash']}: Quarter quantities don't match planned quantity.";
            }

            // Validate non-negative values (EXTENDED to include quantities)
            if ($row['total_budget'] < 0 || $row['planned_budget'] < 0 || $row['total_quantity'] < 0 || $row['planned_quantity'] < 0) {
                $errors[] = "Row #{$row['hash']}: Budget and quantity values cannot be negative.";
            }

            // Validate program
            if (empty(trim($row['program']))) {
                $errors[] = "Row #{$row['hash']}: Program name is required.";
            }
        }

        // Validate parent-child relationships
        foreach ($hashToChildren as $parentHash => $children) {
            $parentRow = current(array_filter($data, fn($r) => $r['hash'] === $parentHash));

            if (!$parentRow) continue;

            $childrenSumPlanned = array_reduce($children, fn($carry, $child) => $carry + $child['planned_budget'], 0);
            if (abs($parentRow['planned_budget'] - $childrenSumPlanned) > 0.01) {
                $errors[] = "Row #{$parentRow['hash']}: Children planned budget sum doesn't match parent.";
            }

            // MODIFIED: Add validation for total_budget and total_quantity sums
            $childrenSumTotalBudget = array_reduce($children, fn($carry, $child) => $carry + $child['total_budget'], 0);
            if (abs($parentRow['total_budget'] - $childrenSumTotalBudget) > 0.01) {
                $errors[] = "Row #{$parentRow['hash']}: Children total budget sum doesn't match parent.";
            }

            $childrenSumTotalQuantity = array_reduce($children, fn($carry, $child) => $carry + $child['total_quantity'], 0);
            if (abs($parentRow['total_quantity'] - $childrenSumTotalQuantity) > 0.01) {
                $errors[] = "Row #{$parentRow['hash']}: Children total quantity sum doesn't match parent.";
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
    }

    private function insertHierarchicalData(array $data, int $projectId, int $fiscalYearId): void
    {
        $hashToId = [];
        $expenditureId = $data[0]['expenditure_id'];

        foreach ($data as $row) {
            $parentDefId = $row['parent_hash'] ? ($hashToId[$row['parent_hash']]['definition_id'] ?? null) : null;

            // MODIFIED: Pass total_budget and total_quantity to upsert definition
            $definition = $this->upsertDefinition(
                $projectId,
                $row['program'],
                $expenditureId,
                $parentDefId,
                $row['total_budget'],
                $row['total_quantity']
            );

            $this->upsertPlan($definition->id, $fiscalYearId, $row);

            $hashToId[$row['hash']] = ['definition_id' => $definition->id];
        }
    }

    private function upsertDefinition(int $projectId, string $program, int $expenditureId, ?int $parentDefId, float $totalBudget, float $totalQuantity)
    {
        $existing = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('program', $program)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            // MODIFIED: Check for conflicts on expenditure_id and parent_id before updating
            if ($existing->expenditure_id !== $expenditureId || $existing->parent_id !== $parentDefId) {
                throw new Exception("Conflicting definition for '{$program}'.");
            }
        }

        $definition = ProjectActivityDefinition::updateOrCreate(
            [
                'project_id' => $projectId,
                'program' => $program,
            ],
            [
                'expenditure_id' => $expenditureId,
                'parent_id' => $parentDefId,
                'total_budget' => $totalBudget,
                'total_quantity' => $totalQuantity,
                'status' => 'active',
            ]
        );

        return $definition;
    }

    private function upsertPlan(int $defId, int $fiscalYearId, array $row): void
    {
        ProjectActivityPlan::updateOrCreate(
            [
                'activity_definition_id' => $defId,
                'fiscal_year_id' => $fiscalYearId
            ],
            [
                // MODIFIED: Removed total_budget and total_quantity (now in definitions)
                'total_expense' => $row['total_expense'],
                'completed_quantity' => $row['completed_quantity'],
                'planned_budget' => $row['planned_budget'],
                'planned_quantity' => $row['planned_quantity'],
                'q1_amount' => $row['q1'],
                'q1_quantity' => $row['q1_quantity'],
                'q2_amount' => $row['q2'],
                'q2_quantity' => $row['q2_quantity'],
                'q3_amount' => $row['q3'],
                'q3_quantity' => $row['q3_quantity'],
                'q4_amount' => $row['q4'],
                'q4_quantity' => $row['q4_quantity'],
            ]
        );
    }
}
