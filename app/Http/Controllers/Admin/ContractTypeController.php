<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractTypeController extends Controller
{
    public function index(): View
    {
        $contractTypes = ContractType::all();

        return view('admin.contractTypes.index', compact('contractTypes'));
    }

    public function create(): View
    {
        return view('admin.contractTypes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:contract_types,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        ContractType::create($validated);

        return redirect()->route('admin.contractType.index')->with('success', 'Contract type created successfully.');
    }

    public function show(ContractType $contractType): View
    {
        return view('admin.contractTypes.show', compact('contractType'));
    }

    public function edit(ContractType $contractType): View
    {
        return view('admin.contractTypes.edit', compact('contractType'));
    }

    public function update(Request $request, ContractType $contractType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:contract_types,code,' . $contractType->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $contractType->update($validated);

        return redirect()->route('admin.contractType.index')->with('success', 'Contract type updated successfully.');
    }

    public function destroy(ContractType $contractType): RedirectResponse
    {
        $contractType->delete();

        return redirect()->route('admin.contractType.index')->with('success', 'Contract type deleted successfully.');
    }
}
