<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use App\Models\PreBudget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PreBudgetReport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return PreBudget::with(['project', 'fiscalYear'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S.N.',
            'Project',
            'Fiscal Year',
            'Internal',
            'Gov. Share',
            'Gov. Loan',
            'Foreign Loan',
            'Foreign Subsidy',
            'Company',
            'Total',
        ];
    }

    public function map($preBudget): array
    {
        static $serial = 0;
        $serial++;

        return [
            $serial,
            $preBudget->project->title ?? '',
            $preBudget->fiscalYear->title ?? '',
            $preBudget->internal_budget,
            $preBudget->government_share,
            $preBudget->government_loan,
            $preBudget->foreign_loan_budget,
            $preBudget->foreign_subsidy_budget,
            $preBudget->company_budget,
            $preBudget->total_budget,
        ];
    }
}
