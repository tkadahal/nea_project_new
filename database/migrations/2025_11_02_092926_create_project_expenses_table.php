<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_activity_plan_id')->constrained('project_activity_plans', 'id')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('project_expenses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('description')->nullable();
            $table->date('effective_date')->nullable();
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['project_activity_plan_id', 'effective_date']);
            $table->index('parent_id');
        });
    }
};
