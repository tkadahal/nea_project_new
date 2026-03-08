<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_budgets', function (Blueprint $table) {

            $table->id();

            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();

            $table->decimal('internal_budget', 20, 2)->default(0);
            $table->decimal('government_share', 20, 2)->default(0);
            $table->decimal('government_loan', 20, 2)->default(0);
            $table->decimal('foreign_loan_budget', 20, 2)->default(0);
            $table->decimal('foreign_subsidy_budget', 20, 2)->default(0);
            $table->decimal('company_budget', 20, 2)->default(0);

            $table->text('foreign_loan_source')->nullable();
            $table->text('foreign_subsidy_source')->nullable();
            $table->text('company_source')->nullable();

            $table->decimal('total_budget', 20, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'fiscal_year_id']);
        });
    }
};
