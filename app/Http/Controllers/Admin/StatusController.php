<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Status\StoreStatusRequest;
use App\Http\Requests\Status\UpdateStatusRequest;
use App\Models\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('status_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $statuses = Status::latest()->get();

        $headers = [trans('global.status.fields.id'), trans('global.status.fields.title'), trans('global.status.fields.color')];
        $data = $statuses->map(function ($status) {
            return [
                'id' => $status->id,
                'title' => $status->title,
                'color' => $status->color,
            ];
        })->all();

        return view('admin.statuses.index', [
            'headers' => $headers,
            'data' => $data,
            'statuses' => $statuses,
            'routePrefix' => 'admin.status',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this status?',
            'arrayColumnColor' => 'blue',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('status_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.statuses.create');
    }

    public function store(StoreStatusRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('status_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        Status::create($request->validated());

        return redirect()->route('admin.status.index');
    }

    public function show(Status $status): View
    {
        abort_if(Gate::denies('status_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.statuses.show', compact('status'));
    }

    public function edit(Status $status)
    {
        abort_if(Gate::denies('status_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.statuses.edit', compact('status'));
    }

    public function update(UpdateStatusRequest $request, Status $status): RedirectResponse
    {
        abort_if(Gate::denies('status_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $status->update($request->validated());

        return redirect()->route('admin.status.index');
    }

    public function destroy(Status $status): RedirectResponse
    {
        abort_if(Gate::denies('status_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $status->delete();

        return back();
    }
}
