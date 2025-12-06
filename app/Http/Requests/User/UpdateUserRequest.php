<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        $authUser = Auth::user();
        $roleIds = $authUser ? $authUser->roles->pluck('id')->toArray() : [];
        $isDirectorateOrProjectUser = in_array(3, $roleIds) || in_array(4, $roleIds);

        return [
            'employee_id' => [
                'required',
                'string',
                Rule::unique('users', 'employee_id')->ignore($userId),
            ],
            'directorate_id' => [
                $isDirectorateOrProjectUser ? 'nullable' : 'nullable',
                'integer',
                'exists:directorates,id',
            ],
            'name' => [
                'required',
                'string',
                'max:250',
            ],
            'mobile_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'mobile_number')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'roles' => [
                $isDirectorateOrProjectUser ? 'sometimes' : 'required',
                'array',
            ],
            'roles.*' => [
                'integer',
                'exists:roles,id',
            ],
            'projects' => [
                'nullable',
                'array',
            ],
            'projects.*' => [
                'integer',
                'exists:projects,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'directorate_id.required' => 'Please select a directorate.',
            'roles.required' => 'Please select at least one role.',
            'email.unique' => 'The email address is already in use.',
            'mobile_number.unique' => 'The mobile number is already in use.',
            'mobile_number.regex' => 'The mobile number must be a valid 10-digit number.',
        ];
    }
}
