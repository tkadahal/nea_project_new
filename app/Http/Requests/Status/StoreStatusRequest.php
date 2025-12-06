<?php

declare(strict_types=1);

namespace App\Http\Requests\Status;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StoreStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('status_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max: 250',
            'color' => 'nullable',
        ];
    }
}
