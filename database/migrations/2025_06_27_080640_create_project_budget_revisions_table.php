<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_budget_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->index()->constrained();
            $table->decimal('internal_budget', 15, 2)->nullable();
            $table->decimal('government_share', 15, 2)->nullable();
            $table->decimal('government_loan', 15, 2)->nullable();
            $table->decimal('foreign_loan_budget', 15, 2)->nullable();
            $table->decimal('foreign_subsidy_budget', 15, 2)->nullable();
            $table->decimal('total_budget', 15, 2)->nullable();

            $table->date('decision_date')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }
};
