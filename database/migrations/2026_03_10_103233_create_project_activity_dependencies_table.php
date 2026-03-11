<?php

declare(strict_types=1);

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
        Schema::create('project_activity_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_id')->constrained('project_activity_schedules')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('project_activity_schedules')->cascadeOnDelete();

            $table->enum('type', ['FS', 'SS', 'FF', 'SF'])->default('FS')
                ->comment(
                    "Dependency / relationship type between activities:\n" .
                        "FS → Finish-to-Start (default)\n" .
                        "SS → Start-to-Start\n" .
                        "FF → Finish-to-Finish\n" .
                        "SF → Start-to-Finish (uncommon)"
                );

            $table->integer('lag_days')->default(0)
                ->comment('Positive = delay, Negative = lead time');

            $table->boolean('is_auto')->default(true)
                ->comment('Auto-created by system or manual');

            $table->timestamps();

            $table->unique(['project_id', 'predecessor_id', 'successor_id'], 'unique_dependency');

            $table->index(['project_id', 'predecessor_id']);
            $table->index(['project_id', 'successor_id']);
        });
    }
};
