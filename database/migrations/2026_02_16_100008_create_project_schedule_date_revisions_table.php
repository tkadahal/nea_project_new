<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_schedule_date_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('project_activity_schedules')->cascadeOnDelete();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('revision_reason');
            $table->text('remarks')->nullable();
            $table->foreignId('revised_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
