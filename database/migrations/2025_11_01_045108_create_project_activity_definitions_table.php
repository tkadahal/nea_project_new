<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activity_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->string('sort_index', 50)->index();
            $table->tinyInteger('depth')->unsigned()->default(0);
            $table->string('program')->nullable();
            $table->tinyInteger('expenditure_id')->unsigned(); // 1 = capital, 2 = recurrent
            $table->text('description')->nullable();
            $table->decimal('total_budget', 18, 2)->default(0.00);
            $table->decimal('total_quantity', 18, 2)->default(0.00);

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('project_activity_definitions')
                ->onDelete('cascade');

            // Versioning
            $table->unsignedBigInteger('version')->default(1);
            $table->foreignId('previous_version_id')
                ->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('versioned_at')->useCurrent();

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'sort_index']);
            $table->index(['project_id', 'version']);
            $table->index(['project_id', 'is_current']);
            $table->index('parent_id');
            $table->index('program');

            // Also keep program unique per project + version (your original rule)
            $table->unique(['project_id', 'program', 'version'], 'unique_program_per_project_version');

            $table->index(['project_id', 'is_current', 'sort_index']);
        });
    }
};
