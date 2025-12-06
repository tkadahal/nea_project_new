<?php

declare(strict_types=1);

namespace App\Http\Requests\FiscalYear;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('fiscalYear_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:fiscal_years,title'],
            'start_date' => ['nullable', 'date', 'before:end_date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}
