<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true; // Adjust based on your authorization logic
    }

    public function rules(): array
    {
        $user = Auth::user();
        $roleIds = $user ? $user->roles->pluck('id')->toArray() : [];
        $isDirectorateOrProjectUser = in_array(3, $roleIds) || in_array(4, $roleIds);

        return [
            'employee_id' => [
                'required',
                'string',
                'unique:users,employee_id',
            ],
            'directorate_id' => [
                $isDirectorateOrProjectUser ? 'nullable' : 'nullable',
                $isDirectorateOrProjectUser ? 'integer' : 'integer',
                $isDirectorateOrProjectUser ? 'exists:directorates,id' : 'exists:directorates,id',
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
                'unique:users,mobile_number',
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email', // Added uniqueness for email
            ],
            'password' => [
                'required',
                'string',
                'min:8', // Added minimum length for security
            ],
            'roles' => [
                $isDirectorateOrProjectUser ? 'sometimes' : 'required',
                'array',
            ],
            'roles.*' => [
                'integer',
                'exists:roles,id', // Added validation to ensure valid role IDs
            ],
            'projects' => [
                'nullable',
                'array',
            ],
            'projects.*' => [
                'integer',
                'exists:projects,id', // Added validation to ensure valid project IDs
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
