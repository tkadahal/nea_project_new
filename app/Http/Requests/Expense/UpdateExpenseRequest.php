<?php

declare(strict_types=1);

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('expense_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'fiscal_year_id' => 'required|integer|exists:fiscal_years,id',
            'budget_type' => 'required|in:internal,foreign_loan,foreign_subsidy',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date',
            'quarter' => 'required|integer|min:1|max:4',
        ];
    }
}
