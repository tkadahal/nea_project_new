<?php

declare(strict_types=1);

namespace App\DTOs\Contract;

class ContractDTO
{
    public function __construct(
        public readonly int $directorateId,
        public readonly int $projectId,
        public readonly int $statusId,
        public readonly int $priorityId,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $contractor = null,
        public readonly ?string $contractAgreementDate = null,
        public readonly ?string $agreementEffectiveDate = null,
        public readonly ?string $agreementCompletionDate = null,
        public readonly float $contractAmount = 0,
        public readonly float $contractVariationAmount = 0,
        public readonly float $progress = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            directorateId: (int) $data['directorate_id'],
            projectId: (int) $data['project_id'],
            statusId: (int) $data['status_id'],
            priorityId: (int) $data['priority_id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            contractor: $data['contractor'] ?? null,
            contractAgreementDate: $data['contract_agreement_date'] ?? null,
            agreementEffectiveDate: $data['agreement_effective_date'] ?? null,
            agreementCompletionDate: $data['agreement_completion_date'] ?? null,
            contractAmount: (float) ($data['contract_amount'] ?? 0),
            contractVariationAmount: (float) ($data['contract_variation_amount'] ?? 0),
            progress: (float) ($data['progress'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'directorate_id' => $this->directorateId,
            'project_id' => $this->projectId,
            'status_id' => $this->statusId,
            'priority_id' => $this->priorityId,
            'title' => $this->title,
            'description' => $this->description,
            'contractor' => $this->contractor,
            'contract_agreement_date' => $this->contractAgreementDate,
            'agreement_effective_date' => $this->agreementEffectiveDate,
            'agreement_completion_date' => $this->agreementCompletionDate,
            'contract_amount' => $this->contractAmount,
            'contract_variation_amount' => $this->contractVariationAmount,
            'progress' => $this->progress,
        ];
    }
}
