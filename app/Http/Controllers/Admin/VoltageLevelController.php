<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoltageLevel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VoltageLevelController extends Controller
{
    public function index(): View
    {
        $voltageLevels = VoltageLevel::all();

        return view('admin.voltageLevels.index', compact('voltageLevels'));
    }

    public function create(): View
    {
        return view('admin.voltageLevels.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|string|max:255',
            'value_kv' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        VoltageLevel::create($validated);

        return redirect()->route('admin.voltageLevel.index')->with('success', 'Voltage level created successfully.');
    }

    public function show(VoltageLevel $voltageLevel): View
    {
        return view('admin.voltageLevels.show', compact('voltageLevel'));
    }

    public function edit(VoltageLevel $voltageLevel): View
    {
        return view('admin.voltageLevels.edit', compact('voltageLevel'));
    }

    public function update(Request $request, VoltageLevel $voltageLevel): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|string|max:255',
            'value_kv' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $voltageLevel->update($validated);

        return redirect()->route('admin.voltageLevel.index')->with('success', 'Voltage level updated successfully.');
    }

    public function destroy(VoltageLevel $voltageLevel): RedirectResponse
    {
        $voltageLevel->delete();

        return redirect()->route('admin.voltageLevel.index')->with('success', 'Voltage level deleted successfully.');
    }
}
