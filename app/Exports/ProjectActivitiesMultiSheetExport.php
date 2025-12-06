<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectActivitiesMultiSheetExport implements WithMultipleSheets
{
    protected $projectId;
    protected $fiscalYearId;
    protected $project;
    protected $fiscalYear;

    public function __construct($projectId, $fiscalYearId, $project, $fiscalYear)
    {
        $this->projectId = $projectId;
        $this->fiscalYearId = $fiscalYearId;
        $this->project = $project;
        $this->fiscalYear = $fiscalYear;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Get capital activities (expenditure_id = 1)
        $capitalActivities = $this->project->projectActivities()
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->where('expenditure_id', 1)
            ->with('children.children') // Load up to 2 levels deep
            ->get();

        // Get recurrent activities (expenditure_id = 2)
        $recurrentActivities = $this->project->projectActivities()
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->where('expenditure_id', 2)
            ->with('children.children')
            ->get();

        // Create Capital sheet
        if ($capitalActivities->isNotEmpty()) {
            $sheets[] = new ProjectActivityExport(
                $this->project,
                $this->fiscalYear,
                $capitalActivities,
                'capital'
            );
        }

        // Create Recurrent sheet
        if ($recurrentActivities->isNotEmpty()) {
            $sheets[] = new ProjectActivityExport(
                $this->project,
                $this->fiscalYear,
                $recurrentActivities,
                'recurrent'
            );
        }

        return $sheets;
    }
}
