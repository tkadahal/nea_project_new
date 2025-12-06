<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->index()->constrained();
            $table->foreignId('fiscal_year_id')->index()->constrained();

            $table->decimal('total_budget', 15, 2)->nullable();
            $table->decimal('internal_budget', 15, 2)->nullable();
            $table->decimal('government_share', 15, 2)->nullable();
            $table->decimal('government_loan', 15, 2)->nullable();
            $table->decimal('foreign_loan_budget', 15, 2)->nullable();
            $table->decimal('foreign_subsidy_budget', 15, 2)->nullable();
            $table->integer('budget_revision')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'fiscal_year_id'], 'budgets_project_id_fiscal_year_id_unique');
        });
    }
};
