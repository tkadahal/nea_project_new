<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Priority\StorePriorityRequest;
use App\Http\Requests\Priority\UpdatePriorityRequest;
use App\Models\Priority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PriorityController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('priority_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $priorities = Priority::latest()->get();

        $headers = [trans('global.priority.fields.id'), trans('global.priority.fields.title')];
        $data = $priorities->map(function ($priority) {
            return [
                'id' => $priority->id,
                'title' => $priority->title,
            ];
        })->all();

        return view('admin.priorities.index', [
            'headers' => $headers,
            'data' => $data,
            'priorities' => $priorities,
            'routePrefix' => 'admin.priority',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this priority?',
            'arrayColumnColor' => 'blue',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('priority_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.priorities.create');
    }

    public function store(StorePriorityRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('priority_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        Priority::create($request->validated());

        return redirect()->route(route: 'admin.priority.index')
            ->with('message', 'Priority created successfully.');
    }

    public function show(Priority $priority): View
    {
        abort_if(Gate::denies('priority_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.priorities.show', compact('priority'));
    }

    public function edit(Priority $priority): View
    {
        abort_if(Gate::denies('priority_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.priorities.edit', compact('priority'));
    }

    public function update(UpdatePriorityRequest $request, Priority $priority): RedirectResponse
    {
        abort_if(Gate::denies('priority_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $priority->update($request->validated());

        return redirect()->route(route: 'admin.priority.index')
            ->with('message', 'Priority updated successfully.');
    }

    public function destroy(Priority $priority): RedirectResponse
    {
        abort_if(Gate::denies('priority_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $priority->delete();

        return back();
    }
}
