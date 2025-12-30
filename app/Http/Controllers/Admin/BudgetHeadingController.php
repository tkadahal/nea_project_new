<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Models\BudgetHeading;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Http\Requests\BudgetHeading\StoreBudgetHeadingRequest;
use App\Http\Requests\BudgetHeading\UpdateBudgetHeadingRequest;

class BudgetHeadingController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('budgetHeading_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budgetHeadings = BudgetHeading::latest()->get();

        $headers = [trans('global.budgetHeading.fields.id'), trans('global.budgetHeading.fields.title'),  trans('global.budgetHeading.fields.description')];
        $data = $budgetHeadings->map(function ($budgetHeading) {
            return [
                'id' => $budgetHeading->id,
                'title' => $budgetHeading->title,
                'description' => $budgetHeading->description,
            ];
        })->all();

        return view('admin.budgetHeadings.index', [
            'headers' => $headers,
            'data' => $data,
            'budgetHeadings' => $budgetHeadings,
            'routePrefix' => 'admin.budgetHeading',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this budgetHeading?',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('budgetHeading_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.budgetHeadings.create');
    }

    public function store(StoreBudgetHeadingRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('budgetHeading_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        BudgetHeading::create($request->validated());

        return redirect()->route('admin.budgetHeading.index');
    }

    public function show(BudgetHeading $budgetHeading): View
    {
        abort_if(Gate::denies('budgetHeading_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.budgetHeadings.show', compact('budgetHeading'));
    }

    public function edit(BudgetHeading $budgetHeading): View
    {
        abort_if(Gate::denies('budgetHeading_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.budgetHeadings.edit', compact('budgetHeading'));
    }

    public function update(UpdateBudgetHeadingRequest $request, BudgetHeading $budgetHeading): RedirectResponse
    {
        abort_if(Gate::denies('budgetHeading_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budgetHeading->update($request->validated());

        return redirect()->route('admin.budgetHeading.index');
    }

    public function destroy(BudgetHeading $budgetHeading): RedirectResponse
    {
        abort_if(Gate::denies('budgetHeading_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budgetHeading->delete();

        return back();
    }
}
