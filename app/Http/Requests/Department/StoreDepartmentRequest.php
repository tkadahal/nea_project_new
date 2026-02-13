<?php

declare(strict_types=1);

namespace App\Http\Requests\Department;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('department_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        $user = Auth::user();
        $roleIds = $user->roles->pluck('id')->toArray();

        $isAdminOrSuperAdmin = in_array(Role::SUPERADMIN, $roleIds)
            || in_array(Role::ADMIN, $roleIds);

        $rules = [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];

        if ($isAdminOrSuperAdmin) {
            $rules['directorate_id'] = [
                'required',
                'integer',
                Rule::exists('directorates', 'id'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Department Title is required',
            'directorate_id.required' => 'Please select a directorate.',
            'directorate_id.exists'   => 'The selected directorate does not exist.',
        ];
    }
}
