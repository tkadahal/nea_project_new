<?php

declare(strict_types=1);

namespace App\Helpers\Budget;

use Normalizer;
use App\Models\Project;

class BudgetHelper
{
    /**
     * Get normalized project titles mapped to IDs
     */
    public static function getNormalizedProjectMap(): array
    {
        return Project::all()->mapWithKeys(function ($project) {
            $title = self::normalizeString($project->title);
            return [$title => $project->id];
        })->toArray();
    }

    /**
     * Normalize string for comparison
     */
    public static function normalizeString(?string $string): string
    {
        if (empty($string)) {
            return '';
        }

        $normalized = normalizer_normalize(trim($string), Normalizer::FORM_C);
        return preg_replace('/\s+/', ' ', $normalized);
    }

    /**
     * Extract fiscal year from Excel row
     */
    public static function extractFiscalYear(?string $row): ?string
    {
        if (empty($row)) {
            return null;
        }

        if (preg_match('/Fiscal Year:\s*(.+)/', $row, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Format budget data for display
     */
    public static function formatBudgetForDisplay(object $budget): array
    {
        return [
            'id' => $budget->id,
            'fiscal_year' => $budget->fiscalYear->title ?? 'N/A',
            'project' => $budget->project->title ?? 'N/A',
            'government_share' => number_format((float) ($budget->government_share ?? 0), 2),
            'government_loan' => number_format((float) ($budget->government_loan ?? 0), 2),
            'foreign_loan_budget' => number_format((float) ($budget->foreign_loan_budget ?? 0), 2),
            'foreign_subsidy_budget' => number_format((float) ($budget->foreign_subsidy_budget ?? 0), 2),
            'internal_budget' => number_format((float) ($budget->internal_budget ?? 0), 2),
            'total_budget' => number_format((float) ($budget->total_budget ?? 0), 2),
            'budget_revision' => $budget->budget_revision,
        ];
    }

    /**
     * Generate template filename
     */
    public static function generateTemplateFilename(string $directorateTitle): string
    {
        $filename = 'budget_template';

        if ($directorateTitle !== 'All Directorates') {
            $filename .= '_' . \Illuminate\Support\Str::slug($directorateTitle);
        }

        $filename .= '_' . now()->format('Y-m-d') . '.xlsx';

        return $filename;
    }
}
