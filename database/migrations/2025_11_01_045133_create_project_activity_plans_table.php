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
            $table->foreignId('activity_definition_id')->constrained('project_activity_definitions')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained()->onDelete('cascade');
            $table->string('program_override')->nullable(); // Year-specific tweak
            $table->timestamp('override_modified_at')->nullable();
            $table->decimal('planned_budget', 15, 2)->default(0);
            $table->decimal('q1_amount', 15, 2)->default(0);
            $table->decimal('q2_amount', 15, 2)->default(0);
            $table->decimal('q3_amount', 15, 2)->default(0);
            $table->decimal('q4_amount', 15, 2)->default(0);
            $table->decimal('planned_quantity', 10, 2)->default(0);
            $table->decimal('q1_quantity', 10, 2)->default(0);
            $table->decimal('q2_quantity', 10, 2)->default(0);
            $table->decimal('q3_quantity', 10, 2)->default(0);
            $table->decimal('q4_quantity', 10, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0); // Computed from expenses
            $table->decimal('completed_quantity', 10, 2)->default(0); // Computed
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['activity_definition_id', 'fiscal_year_id']);
            $table->index(['deleted_at', 'activity_definition_id', 'fiscal_year_id']);
        });
    }
};
