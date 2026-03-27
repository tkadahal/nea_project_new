<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('contract_activity_schedules')->onDelete('cascade');
            $table->decimal('progress', 5, 2)->default(0.00);
            $table->decimal('target_quantity', 15, 2)->nullable();
            $table->decimal('completed_quantity', 15, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->boolean('use_quantity_tracking')->default(false)->after('unit');
            $table->enum('status', ['active', 'not_needed', 'cancelled', 'completed'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'schedule_id']);

            $table->index('progress');
        });
    }
};
