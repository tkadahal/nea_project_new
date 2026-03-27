<?php

declare(strict_types=1);

namespace App\Helpers\Contract;

use App\Models\Contract;

class ContractHelper
{
    public static function formatContractForDisplay(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'title' => $contract->title ?? 'Untitled',
            'description' => $contract->description ?? 'No description available',
            'directorate' => $contract->directorate?->title ?? 'N/A',
            'directorate_id' => $contract->directorate?->id,
            'project' => $contract->project?->title ?? 'N/A',
            'project_id' => $contract->project?->id,
            'status' => $contract->status?->title ?? 'N/A',
            'status_id' => $contract->status?->id,
            'priority' => $contract->priority?->title ?? 'N/A',
            'priority_id' => $contract->priority?->id,
            'contract_agreement_date' => $contract->contract_agreement_date?->format('Y-m-d') ?? 'N/A',
            'agreement_effective_date' => $contract->agreement_effective_date?->format('Y-m-d') ?? 'N/A',
            'agreement_completion_date' => $contract->agreement_completion_date?->format('Y-m-d') ?? 'N/A',
            'contract_amount' => number_format((float) ($contract->contract_amount ?? 0), 2),
            'progress' => self::getFormattedProgress($contract),
            'progress_value' => (float) ($contract->getCachedPhysicalProgress() ?? 0),
        ];
    }

    public static function formatContractForTable(Contract $contract, array $priorityColors = []): array
    {
        $priorityValue = $contract->priority?->title ?? 'N/A';
        $priorityColor = $priorityColors[$priorityValue] ?? '#6B7280';

        return [
            'id' => $contract->id,
            'title' => $contract->title ?? 'Untitled',
            'fields' => [
                [
                    'title' => trans('global.contract.fields.contract_agreement_date') . ': ' .
                        ($contract->contract_agreement_date?->format('Y-m-d') ?? 'N/A'),
                    'color' => 'gray',
                ],
                [
                    'title' => trans('global.contract.fields.agreement_completion_date') . ': ' .
                        ($contract->agreement_completion_date?->format('Y-m-d') ?? 'N/A'),
                    'color' => 'gray',
                ],
                [
                    'title' => trans('global.contract.fields.contract_amount') . ': ' .
                        number_format((float) ($contract->contract_amount ?? 0), 2),
                    'color' => 'blue',
                ],
                [
                    'title' => trans('global.contract.fields.progress') . ': ' .
                        (is_numeric($contract->getCachedPhysicalProgress())
                            ? $contract->getCachedPhysicalProgress() . '%'
                            : 'N/A'),
                    'color' => 'green',
                ],
                [
                    'title' => trans('global.contract.fields.priority_id') . ': ' . $priorityValue,
                    'color' => $priorityColor,
                ],
            ],
        ];
    }

    public static function formatContractForCard(
        Contract $contract,
        array $directorateColors = [],
        array $priorityColors = []
    ): array {
        $directorateTitle = $contract->directorate?->title ?? 'N/A';
        $directorateId = $contract->directorate?->id;
        $priorityValue = $contract->priority?->title ?? 'N/A';
        $priorityColor = $priorityColors[$priorityValue] ?? '#6B7280';
        $projectTitle = $contract->project?->title ?? 'N/A';

        return [
            'id' => $contract->id,
            'title' => $contract->title ?? 'Untitled',
            'description' => $contract->description ?? 'No description available',
            'directorate' => ['title' => $directorateTitle, 'id' => $directorateId],
            'fields' => [
                [
                    'label' => trans('global.contract.fields.contract_agreement_date'),
                    'key' => 'contract_agreement_date',
                    'value' => $contract->contract_agreement_date?->format('Y-m-d') ?? 'N/A',
                    'color' => 'yellow',
                ],
                [
                    'label' => trans('global.contract.fields.agreement_effective_date'),
                    'key' => 'agreement_effective_date',
                    'value' => $contract->agreement_effective_date?->format('Y-m-d') ?? 'N/A',
                    'color' => 'green',
                ],
                [
                    'label' => trans('global.contract.fields.agreement_completion_date'),
                    'key' => 'agreement_completion_date',
                    'value' => $contract->agreement_completion_date?->format('Y-m-d') ?? 'N/A',
                    'color' => 'red',
                ],
                [
                    'label' => trans('global.contract.fields.contract_amount'),
                    'key' => 'contract_amount',
                    'value' => number_format((float) ($contract->contract_amount ?? 0), 2),
                    'color' => 'orange',
                ],
                [
                    'label' => trans('global.contract.fields.progress'),
                    'key' => 'progress',
                    'value' => is_numeric($contract->getCachedPhysicalProgress())
                        ? $contract->getCachedPhysicalProgress() . '%'
                        : 'N/A',
                    'color' => 'yellow',
                ],
                [
                    'label' => trans('global.contract.fields.status_id'),
                    'key' => 'status',
                    'value' => $contract->status?->title ?? 'N/A',
                ],
                [
                    'label' => trans('global.contract.fields.priority_id'),
                    'key' => 'priority',
                    'value' => $priorityValue,
                    'color' => $priorityColor,
                ],
                [
                    'label' => trans('global.contract.fields.directorate_id'),
                    'key' => 'directorate',
                    'value' => $directorateTitle,
                    'color' => $directorateColors[$directorateId] ?? 'gray',
                ],
                [
                    'label' => trans('global.contract.fields.project_id'),
                    'key' => 'project',
                    'value' => $projectTitle,
                    'color' => 'yellow',
                ],
            ],
        ];
    }

    public static function getDefaultDirectorateColors(): array
    {
        return config('colors.directorate', []);
    }

    public static function getDefaultPriorityColors(): array
    {
        return config('colors.priority', []);
    }

    public static function ensureAllDirectoratesHaveColors(
        array $directorateColors,
        array $allDirectorateIds
    ): array {
        foreach ($allDirectorateIds as $id) {
            if (!isset($directorateColors[$id])) {
                $directorateColors[$id] = 'gray';
            }
        }

        return $directorateColors;
    }

    /**
     * Returns formatted progress string (e.g., "75%")
     */
    public static function getFormattedProgress(Contract $contract): string
    {
        $progress = self::calculateContractProgressFromLoaded($contract);
        return is_numeric($progress) ? $progress . '%' : 'N/A';
    }

    /**
     * Calculate contract progress based on activity schedules and weightage
     */
    private static function calculateContractProgressFromLoaded(Contract $contract): float
    {
        $allSchedules = $contract->activitySchedules;

        $activeSchedules = $allSchedules->where('pivot.status', 'active');
        $topLevel = $activeSchedules->where('level', 1)->whereNotNull('weightage');

        if ($topLevel->isEmpty()) {
            return 0.0;
        }

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $weight = (float) $schedule->weightage;

            $leaves = self::collectLeavesFromCollection($schedule, $allSchedules);

            $avgProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($avgProgress * $weight);
            $totalWeightage += $weight;
        }

        return $totalWeightage > 0
            ? round($totalWeightedProgress / $totalWeightage, 2)
            : 0.0;
    }

    /**
     * Recursively collect leaf nodes (activities without children)
     */
    private static function collectLeavesFromCollection($current, $allSchedules)
    {
        $children = $allSchedules->where('parent_id', $current->id);

        if ($children->isNotEmpty()) {
            $leaves = collect();
            foreach ($children as $child) {
                $leaves = $leaves->merge(
                    self::collectLeavesFromCollection($child, $allSchedules)
                );
            }
            return $leaves;
        }

        $validStatuses = ['active', 'completed'];

        if (isset($current->pivot) && in_array($current->pivot->status, $validStatuses)) {
            return collect([$current]);
        }

        return collect([]);
    }
}
