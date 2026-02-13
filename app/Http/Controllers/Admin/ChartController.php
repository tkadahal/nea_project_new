<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Models\ProjectActivityDefinition;

class ChartController extends Controller
{
    public function activityTree($projectId): View
    {
        $roots = ProjectActivityDefinition::where('project_id', $projectId)
            ->whereNull('parent_id')
            ->where('is_current', true)
            ->with('descendants')
            ->get();

        $roots = ProjectActivityDefinition::sortNaturally($roots);

        $capitalRoots = $roots->where('expenditure_id', 1)->values();
        $recurrentRoots = $roots->where('expenditure_id', 2)->values();

        return view('admin.charts.activity-tree-chart', compact('capitalRoots', 'recurrentRoots', 'projectId'));
    }
}
