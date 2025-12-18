<?php

declare(strict_types=1);

namespace App\Http\Requests\BudgetHeading;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class StoreBudgetHeadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('budgetHeading_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                'unique:budget_headings,title',
            ],
            'description' => [
                'nullable',
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'शीर्षक अनिवार्य छ।',
            'title.unique'   => 'यो शीर्षक पहिले नै प्रयोग भइसकेको छ।',
            'title.max'      => 'शीर्षक २५५ अक्षरभन्दा बढी हुनु हुँदैन।',
        ];
    }
}
