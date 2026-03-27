<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_schedule_date_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('contract_activity_schedules')->cascadeOnDelete();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('revision_reason');
            $table->text('remarks')->nullable();
            $table->foreignId('revised_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
