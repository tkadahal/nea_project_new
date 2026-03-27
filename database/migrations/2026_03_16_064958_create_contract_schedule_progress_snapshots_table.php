<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_schedule_progress_snapshots', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('contract_activity_schedules')->onDelete('cascade');

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
            $table->index(['contract_id', 'schedule_id', 'snapshot_date']);
            $table->index(['contract_id', 'snapshot_type']);
            $table->index('snapshot_date');
        });
    }
};
