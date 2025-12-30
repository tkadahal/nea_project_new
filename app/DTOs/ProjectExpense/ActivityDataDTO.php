<?php

declare(strict_types=1);

namespace App\DTOs\ProjectExpense;

class ActivityDataDTO
{
    public function __construct(
        public readonly int $activityId,
        public readonly ?int $parentActivityId,
        public readonly float $qty,
        public readonly float $amt,
        public readonly ?string $description = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            activityId: (int) $data['activity_id'],
            parentActivityId: isset($data['parent_activity_id']) ? (int) $data['parent_activity_id'] : null,
            qty: (float) ($data['qty'] ?? 0),
            amt: (float) ($data['amt'] ?? 0),
            description: $data['description'] ?? null
        );
    }
}
