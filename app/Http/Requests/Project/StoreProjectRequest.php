<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('project_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'directorate_id' => ['required', 'exists:directorates,id'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'budget_heading_id' => ['nullable', 'exists:budget_headings,id'],
            'lmbis_id' => [
                'nullable',
                'integer',
                Rule::unique('projects', 'lmbis_id')->where('budget_heading_id', $this->budget_heading_id),
            ],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location'    => ['nullable', 'string'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status_id'   => ['required', 'exists:statuses,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'project_manager' => ['nullable', 'exists:users,id'],
            'files'       => ['nullable', 'array'],
            'files.*'     => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    /**
     * Configure the validator instance.
     * This prevents non-admins from submitting a directorate ID that isn't theirs.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $roleIds = $user->roles->pluck('id')->toArray();

            // Check if user is Admin or SuperAdmin
            $isAdmin = in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds);

            // If NOT an admin, verify the submitted directorate matches the user's directorate
            if (!$isAdmin) {
                $submittedDirectorateId = (string) $this->input('directorate_id');
                $userDirectorateId = (string) $user->directorate_id;

                if ($submittedDirectorateId !== $userDirectorateId) {
                    $validator->errors()->add('directorate_id', 'You are not authorized to create projects for this directorate.');
                }
            }
        });
    }
}
