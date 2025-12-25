<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expense_funding_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->onDelete('cascade');
            $table->unsignedTinyInteger('quarter')->nullable(); // 1-4
            $table->decimal('internal_budget', 18, 2)->default(0);
            $table->decimal('government_share', 18, 2)->default(0);
            $table->decimal('government_loan', 18, 2)->default(0);
            $table->decimal('foreign_loan_budget', 18, 2)->default(0);
            $table->decimal('foreign_subsidy_budget', 18, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
