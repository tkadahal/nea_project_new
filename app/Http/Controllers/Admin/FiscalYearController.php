<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\FiscalYear\StoreFiscalYearRequest;
use App\Http\Requests\FiscalYear\UpdateFiscalYearRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class FiscalYearController extends Controller
{
    public function index(): View
    {
        abort_if(Gate::denies('fiscalYear_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $fiscalYears = FiscalYear::all();

        $headers = [trans('global.fiscalYear.fields.id'), trans('global.fiscalYear.fields.title'), trans('global.fiscalYear.fields.start_date'), trans('global.fiscalYear.fields.end_date')];
        $data = $fiscalYears->map(function ($fiscalYear) {
            return [
                'id' => $fiscalYear->id,
                'title' => $fiscalYear->title,
                'start_date' => $fiscalYear->start_date,
                'end_date' => $fiscalYear->end_date,
            ];
        })->all();

        return view('admin.fiscalYears.index', [
            'headers' => $headers,
            'data' => $data,
            'fiscalYears' => $fiscalYears,
            'routePrefix' => 'admin.fiscalYear',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this Fiscal Year?',
        ]);
    }

    public function create(): View
    {
        abort_if(Gate::denies('fiscalYear_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.fiscalYears.create');
    }

    public function store(StoreFiscalYearRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('fiscalYear_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        FiscalYear::create($request->validated());

        return redirect()->route('admin.fiscalYear.index');
    }

    public function show(FiscalYear $fiscalYear): View
    {
        abort_if(Gate::denies('fiscalYear_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.fiscalYears.show', compact('fiscalYear'));
    }

    public function edit(FiscalYear $fiscalYear): View
    {
        abort_if(Gate::denies('fiscalYear_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.fiscalYears.edit', compact('fiscalYear'));
    }


    public function update(UpdateFiscalYearRequest $request, FiscalYear $fiscalYear)
    {
        abort_if(Gate::denies('fiscalYear_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $fiscalYear->update($request->validated());

        return redirect()->route('admin.fiscalYear.index');
    }

    public function destroy(FiscalYear $fiscalYear): RedirectResponse
    {
        abort_if(Gate::denies('fiscalYear_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $fiscalYear->delete();

        return back();
    }
}
