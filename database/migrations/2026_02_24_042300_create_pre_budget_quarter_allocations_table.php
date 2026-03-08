<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_budget_quarter_allocations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('pre_budget_id')->constrained('pre_budgets')->cascadeOnDelete();

            $table->unsignedTinyInteger('quarter');

            $table->decimal('internal_budget', 20, 2)->default(0);
            $table->decimal('government_share', 20, 2)->default(0);
            $table->decimal('government_loan', 20, 2)->default(0);
            $table->decimal('foreign_loan_budget', 20, 2)->default(0);
            $table->decimal('foreign_subsidy_budget', 20, 2)->default(0);
            $table->decimal('company_budget', 20, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pre_budget_id', 'quarter']);
        });
    }
};
