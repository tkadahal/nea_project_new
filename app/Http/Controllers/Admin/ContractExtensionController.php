<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractExtension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ContractExtensionController extends Controller
{
    public function create(Contract $contract): View
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.contract-extensions.create', compact('contract'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'extension_period' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
            'approval_date' => 'required|date',
            // Add other validation rules as needed
        ]);

        $extension = $contract->extensions()->create([
            'extension_period' => $validated['extension_period'],
            'reason' => $validated['reason'],
            'approved_by' => Auth::id(),
            'approval_date' => $validated['approval_date'],
            // Calculate new_completion_date, e.g.:
            'new_completion_date' => $contract->agreement_completion_date->addDays($validated['extension_period']),
        ]);

        return redirect()->route('admin.contract.show', $contract)->with('success', 'Extension added successfully.');
    }

    // Optional: Methods for edit, update, delete extensions
    public function edit(Contract $contract, ContractExtension $extension): View
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.contract-extensions.edit', compact('contract', 'extension'));
    }

    public function update(Request $request, Contract $contract, ContractExtension $extension): RedirectResponse
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'extension_period' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
            'approval_date' => 'required|date',
        ]);

        $extension->update($validated);
        // Recalculate new_completion_date if needed

        return redirect()->route('admin.contract.show', $contract)->with('success', 'Extension updated successfully.');
    }

    public function destroy(Contract $contract, ContractExtension $extension): RedirectResponse
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $extension->delete();

        return back()->with('success', 'Extension deleted successfully.');
    }
}
