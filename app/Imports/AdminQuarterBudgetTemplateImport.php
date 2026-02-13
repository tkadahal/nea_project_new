<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Project;
use App\Models\Budget;
use App\Models\BudgetQuaterAllocation;
use App\Models\FiscalYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\Importable;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminQuarterBudgetTemplateImport implements
    ToCollection,
    WithStartRow,
    WithValidation,
    SkipsEmptyRows
{
    use Importable;

    protected UploadedFile $file;
    protected array $projectMap = [];
    protected int $fiscalYearId;
    protected ?int $currentProjectId = null;

    protected array $budgetTypeMap = [
        'Government Share'   => 'government_share',
        'Government Loan'    => 'government_loan',
        'Foreign Loan'       => 'foreign_loan_budget',
        'Foreign Subsidy'    => 'foreign_subsidy_budget',
        'Internal Resources' => 'internal_budget',
    ];

    protected array $quarterFieldMap = [
        'government_share'       => 'government_share',
        'government_loan'        => 'government_loan',
        'foreign_loan_budget'    => 'foreign_loan',
        'foreign_subsidy_budget' => 'foreign_subsidy',
        'internal_budget'        => 'internal_budget',
    ];

    protected array $sourceFields = [
        'Foreign Loan'    => 'foreign_loan_source',
        'Foreign Subsidy' => 'foreign_subsidy_source',
    ];

    public function __construct(UploadedFile $file)
    {
        $this->file = $file;

        Project::select('id', 'title')->chunk(1000, function ($projects) {
            foreach ($projects as $project) {
                $normalized = $this->normalizeTitle($project->title);
                $this->projectMap[$normalized] = $project->id;
                $this->projectMap[$project->title] = $project->id;
            }
        });

        $this->fiscalYearId = $this->extractFiscalYearId();
    }

    private function extractFiscalYearId(): int
    {
        try {
            $spreadsheet = IOFactory::load($this->file->getPathname());
            $worksheet   = $spreadsheet->getActiveSheet();

            $cellValue = $worksheet->getCell('C3')->getValue();

            if (empty($cellValue)) {
                throw new \Exception('Fiscal year cell (C3) is empty in the uploaded template.');
            }

            $fiscalYearTitle = trim(preg_replace('/^Fiscal\s+Year\s*:\s*/i', '', (string) $cellValue));

            Log::info('Fiscal year extracted from C3', [
                'raw'     => $cellValue,
                'cleaned' => $fiscalYearTitle,
            ]);

            $fy = FiscalYear::where('title', $fiscalYearTitle)->first();

            if (!$fy) {
                throw new \Exception("Fiscal year '{$fiscalYearTitle}' not found in database.");
            }

            return $fy->id;
        } catch (\Exception $e) {
            Log::error('Fiscal year extraction failed', [
                'error' => $e->getMessage(),
                'file'  => $this->file->getClientOriginalName(),
            ]);

            throw $e;
        }
    }

    public function startRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $data = $row->toArray();
                $rowIndex = $index + 5;

                $projectTitle  = trim($data[1] ?? '');
                $budgetType    = trim($data[2] ?? '');
                $totalApproved = $this->toFloat($data[3] ?? 0);
                $q1            = $this->toFloat($data[4] ?? 0);
                $q2            = $this->toFloat($data[5] ?? 0);
                $q3            = $this->toFloat($data[6] ?? 0);
                $q4            = $this->toFloat($data[7] ?? 0);
                $source        = trim($data[8] ?? '');

                if (
                    empty($projectTitle) && empty($budgetType) &&
                    $totalApproved <= 0 && $q1 <= 0 && $q2 <= 0 && $q3 <= 0 && $q4 <= 0
                ) {
                    continue;
                }

                if ($projectTitle) {
                    $normalized = $this->normalizeTitle($projectTitle);
                    $this->currentProjectId = $this->projectMap[$normalized] ??
                        $this->projectMap[$projectTitle] ?? null;
                }

                if (!$this->currentProjectId || !$budgetType) continue;

                $budgetField = $this->budgetTypeMap[$budgetType] ?? null;
                if (!$budgetField) continue;

                $budget = Budget::firstOrCreate(
                    [
                        'project_id'     => $this->currentProjectId,
                        'fiscal_year_id' => $this->fiscalYearId,
                    ],
                    ['total_budget' => 0]
                );

                $updateData = [$budgetField => $totalApproved];
                if (isset($this->sourceFields[$budgetType]) && $source !== '') {
                    $updateData[$this->sourceFields[$budgetType]] = $source;
                }
                $budget->update($updateData);

                $budget->total_budget = array_sum([
                    $budget->government_share ?? 0,
                    $budget->government_loan ?? 0,
                    $budget->foreign_loan_budget ?? 0,
                    $budget->foreign_subsidy_budget ?? 0,
                    $budget->internal_budget ?? 0,
                ]);
                $budget->saveQuietly();

                $quarterField = $this->quarterFieldMap[$budgetField];
                $quarters = ['Q1' => $q1, 'Q2' => $q2, 'Q3' => $q3, 'Q4' => $q4];

                foreach ($quarters as $qKey => $amount) {
                    if ($amount <= 0) continue;

                    BudgetQuaterAllocation::updateOrCreate(
                        ['budget_id' => $budget->id, 'quarter' => $qKey],
                        [$quarterField => $amount]
                    );
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.3' => 'nullable|numeric|min:0',
            '*.4' => 'nullable|numeric|min:0',
            '*.5' => 'nullable|numeric|min:0',
            '*.6' => 'nullable|numeric|min:0',
            '*.7' => 'nullable|numeric|min:0',
        ];
    }

    private function normalizeTitle(string $title): string
    {
        $nep = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        $eng = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return trim(preg_replace('/\s+/u', ' ', str_replace($nep, $eng, $title)));
    }

    private function toFloat($value): float
    {
        if (is_numeric($value)) return (float) $value;
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return $cleaned === '' ? 0.0 : (float) $cleaned;
    }
}
