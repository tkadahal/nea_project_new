<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedule_progress_snapshots', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('project_activity_schedules')->onDelete('cascade');

            // Progress data at this point in time
            $table->decimal('progress', 5, 2)->default(0);
            $table->decimal('completed_quantity', 15, 2)->nullable();
            $table->decimal('target_quantity', 15, 2)->nullable();
            $table->string('unit', 50)->nullable();

            // Metadata
            $table->string('snapshot_type', 20)->default('manual');
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp
            $table->timestamp('snapshot_date');
            $table->timestamps();

            // Indexes for fast queries
            $table->index(['project_id', 'schedule_id', 'snapshot_date']);
            $table->index(['project_id', 'snapshot_type']);
            $table->index('snapshot_date');
        });
    }
};
