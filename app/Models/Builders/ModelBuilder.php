<?php

declare(strict_types=1);

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class ModelBuilder extends Builder
{
    public function active(): self
    {
        return $this->where('active', true);
    }

    public function draft(): self
    {
        return $this->where('active', false);
    }
}
