<?php

declare(strict_types=1);

namespace App\Helpers\ProjectExpense;

use App\Models\ProjectActivityPlan;

class ExcelRowParser
{
    public function parseRows(array $rows, int $projectId, int $fiscalYearId): array
    {
        $processed = [];

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->shouldSkipRow($row)) {
                continue;
            }

            $planId = $this->extractPlanId($row, $projectId, $fiscalYearId);

            if ($planId) {
                $processed[] = [
                    'activity_id' => $planId,
                    'qty' => (float) ($row[6] ?? 0),
                    'amt' => (float) ($row[7] ?? 0),
                    'parent_activity_id' => $this->deriveParentId($i, $rows, $projectId, $fiscalYearId),
                ];
            }
        }

        return $processed;
    }

    private function shouldSkipRow(array $row): bool
    {
        $depth = $row[8] ?? 0;
        $title = trim($row[1] ?? '');

        return $depth < 0 || str_contains($title, 'जम्मा') || empty($title);
    }

    private function extractPlanId(array $row, int $projectId, int $fiscalYearId): ?int
    {
        $planId = $row[9] ?? null;

        if ($planId) {
            return $planId;
        }

        $title = trim($row[1] ?? '');

        $plan = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereHas('definitionVersion', function ($q) use ($projectId, $title) {
                $q->where('project_id', $projectId)
                    ->whereRaw('LOWER(program) LIKE ?', ['%' . strtolower($title) . '%']);
            })
            ->first();

        return $plan?->id;
    }

    private function deriveParentId(int $rowIndex, array $rows, int $projectId, int $fiscalYearId): ?int
    {
        $current = $rows[$rowIndex];
        $currentDepth = $current[8] ?? 0;
        $currentSerial = (string) ($current[0] ?? '');

        if ($currentDepth <= 0 || $currentSerial === '') {
            return null;
        }

        // Try to find parent by serial number
        $parentId = $this->findParentBySerial($currentSerial, $rows, $rowIndex);
        if ($parentId) {
            return $parentId;
        }

        // Fallback: find parent by depth
        return $this->findParentByDepth($currentDepth, $rows, $rowIndex);
    }

    private function findParentBySerial(string $currentSerial, array $rows, int $rowIndex): ?int
    {
        $parts = explode('.', $currentSerial);

        if (count($parts) > 1) {
            array_pop($parts);
            $parentSerial = implode('.', $parts);

            foreach (array_slice($rows, 0, $rowIndex) as $prev) {
                if ((string) ($prev[0] ?? '') === $parentSerial) {
                    return $prev[9] ?? null;
                }
            }
        }

        return null;
    }

    private function findParentByDepth(int $currentDepth, array $rows, int $rowIndex): ?int
    {
        for ($i = $rowIndex - 1; $i >= 0; $i--) {
            if (($rows[$i][8] ?? 0) == $currentDepth - 1) {
                return $rows[$i][9] ?? null;
            }
        }

        return null;
    }
}
