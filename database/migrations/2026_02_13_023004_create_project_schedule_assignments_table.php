<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('project_activity_schedules')->onDelete('cascade');
            $table->decimal('progress', 5, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Unique constraint - each schedule can only be assigned once per project
            $table->unique(['project_id', 'schedule_id']);

            // Indexes
            $table->index('progress');
        });
    }
};
