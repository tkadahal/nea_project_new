<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activity_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('project_activity_schedules')->onDelete('cascade');
            $table->decimal('weightage', 5, 2)->nullable();
            $table->string('default_unit', 50)->nullable();
            $table->text('unit_suggestions')->nullable();
            $table->enum('project_type', ['transmission_line', 'substation', 'generation']);
            $table->integer('level')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('auto_assign_to_projects')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_type', 'level']);
            $table->index('parent_id');

            $table->unique(['code', 'project_type']);
        });

        Schema::create('schedule_auto_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('project_activity_schedules')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->string('assigned_by')->nullable();
            $table->text('notes')->nullable();

            $table->index(['schedule_id', 'project_id']);
        });
    }
};
