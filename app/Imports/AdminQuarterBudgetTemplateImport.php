<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\Budget;
use App\Models\BudgetQuaterAllocation;
use App\Models\FiscalYear;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\DB;

class AdminQuarterBudgetTemplateImport implements ToCollection, WithStartRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    protected $projectMap = [];
    protected $budgetTypeMap = [];
    protected $sourceFields = [];
    protected $currentFiscalYearId;

    protected $currentProjectId = null;
    protected $currentBudget = null;

    public function __construct()
    {
        $currentFiscalYear = FiscalYear::currentFiscalYear();
        if (!$currentFiscalYear) {
            throw new \Exception('No current fiscal year found.');
        }
        $this->currentFiscalYearId = $currentFiscalYear->id;

        $this->budgetTypeMap = [
            'Government Share'     => 'government_share',
            'Government Loan'      => 'government_loan',
            'Foreign Loan'         => 'foreign_loan_budget',
            'Foreign Subsidy'      => 'foreign_subsidy_budget',
            'Internal Resources'   => 'internal_budget',
        ];

        $this->sourceFields = [
            'Foreign Loan'    => 'foreign_loan_source',
            'Foreign Subsidy' => 'foreign_subsidy_source',
        ];

        // Load projects and normalize titles for matching
        $projects = Project::select('id', 'title')->get();
        foreach ($projects as $project) {
            $normalized = $this->normalizeTitle($project->title);
            $this->projectMap[$normalized] = $project->id;
            // Also keep original as fallback
            $this->projectMap[$project->title] = $project->id;
        }
    }

    public function startRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        foreach ($rows as $row) {
            $data = $row->toArray();

            $projectTitle   = trim($data[1] ?? '');
            $budgetType     = trim($data[2] ?? '');
            $totalApproved  = $this->toFloat($data[3] ?? 0);
            $q1             = $this->toFloat($data[4] ?? 0);
            $q2             = $this->toFloat($data[5] ?? 0);
            $q3             = $this->toFloat($data[6] ?? 0);
            $q4             = $this->toFloat($data[7] ?? 0);
            $source         = trim($data[8] ?? '');

            if (!empty($projectTitle)) {
                $normalized = $this->normalizeTitle($projectTitle);

                if (!isset($this->projectMap[$normalized]) && !isset($this->projectMap[$projectTitle])) {
                    // Find closest matches for better error message
                    $closest = $this->findClosestMatches($projectTitle, array_keys($this->projectMap), 3);

                    $suggestion = $closest ? "Did you mean: " . implode(', ', $closest) . "?" : "";
                    throw new \Exception("Project not found: '{$projectTitle}'. {$suggestion} Title must match a project in the system.");
                }

                $this->currentProjectId = $this->projectMap[$normalized] ?? $this->projectMap[$projectTitle];

                $this->currentBudget = Budget::firstOrCreate(
                    [
                        'project_id'     => $this->currentProjectId,
                        'fiscal_year_id' => $this->currentFiscalYearId,
                    ],
                    ['total_budget' => 0]
                );
            }

            if (!$this->currentBudget || empty($budgetType)) {
                continue;
            }

            $field = $this->budgetTypeMap[$budgetType] ?? null;
            if (!$field) {
                continue;
            }

            $updateData = [$field => $totalApproved];
            if (isset($this->sourceFields[$budgetType]) && !empty($source)) {
                $updateData[$this->sourceFields[$budgetType]] = $source;
            }
            $this->currentBudget->update($updateData);

            $quarters = ['Q1' => $q1, 'Q2' => $q2, 'Q3' => $q3, 'Q4' => $q4];
            foreach ($quarters as $quarter => $amount) {
                BudgetQuaterAllocation::updateOrCreate(
                    [
                        'budget_id' => $this->currentBudget->id,
                        'quarter'   => $quarter,
                    ],
                    [$field => $amount]
                );
            }
        }

        DB::commit();
    }

    public function rules(): array
    {
        return [
            '*.3' => 'nullable|numeric|min:0',
            '*.4' => 'nullable|numeric|min:0',
            '*.5' => 'nullable|numeric|min:0',
            '*.6' => 'nullable|numeric|min:0',
            '*.7' => 'nullable|numeric|min:0',
            '*.8' => 'nullable|string|max:255',
        ];
    }

    private function normalizeTitle(string $title): string
    {
        // Convert Nepali numerals to English
        $nepaliNumerals = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $title = str_replace($nepaliNumerals, $englishNumerals, $title);

        // Normalize common variations
        return trim(preg_replace([
            '/\s+/u',           // multiple spaces → one
            '/[.\s]*के\.भि\.?[.\s]*/ui',  // के.भि. or के.भी. → केभी
            '/[.\s]*के\.भी\.?[.\s]*/ui',
        ], ' ', $title));
    }

    private function findClosestMatches(string $title, array $candidates, int $limit = 3): array
    {
        $normalized = $this->normalizeTitle($title);
        $distances = [];

        foreach ($candidates as $candidate) {
            similar_text($normalized, $this->normalizeTitle($candidate), $percent);
            $distances[$candidate] = $percent;
        }

        arsort($distances);
        return array_slice(array_keys($distances), 0, $limit);
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_numeric($value)) return (float) $value;
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return $cleaned === '' || $cleaned === '.' || $cleaned === '-' ? 0.0 : (float) $cleaned;
    }
}
