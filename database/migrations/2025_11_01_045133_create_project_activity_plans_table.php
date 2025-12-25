<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activity_plans', function (Blueprint $table) {
            $table->id();

            // ONLY the versioned definition ID — authoritative
            $table->foreignId('activity_definition_version_id')
                ->constrained('project_activity_definitions')
                ->onDelete('restrict'); // Prevent deleting a version that has plans

            $table->foreignId('fiscal_year_id')->constrained()->onDelete('cascade');

            $table->string('program_override')->nullable();
            $table->timestamp('override_modified_at')->nullable();

            // Planned values
            $table->decimal('planned_budget', 18, 2)->default(0.00);
            $table->decimal('q1_amount', 18, 2)->default(0.00);
            $table->decimal('q2_amount', 18, 2)->default(0.00);
            $table->decimal('q3_amount', 18, 2)->default(0.00);
            $table->decimal('q4_amount', 18, 2)->default(0.00);
            $table->decimal('planned_quantity', 18, 2)->default(0.00);
            $table->decimal('q1_quantity', 18, 2)->default(0.00);
            $table->decimal('q2_quantity', 18, 2)->default(0.00);
            $table->decimal('q3_quantity', 18, 2)->default(0.00);
            $table->decimal('q4_quantity', 18, 2)->default(0.00);

            // Actuals
            $table->decimal('total_expense', 18, 2)->default(0.00);
            $table->decimal('completed_quantity', 18, 2)->default(0.00);

            // Workflow
            $table->enum('status', ['draft', 'under_review', 'approved'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // UNIQUE: One plan per specific versioned activity per fiscal year
            $table->unique(['activity_definition_version_id', 'fiscal_year_id']);

            // Indexes
            $table->index(['fiscal_year_id', 'status']);
            // $table->index(['activity_definition_version_id', 'fiscal_year_id']);
            $table->index(['deleted_at']);
        });
    }
};
