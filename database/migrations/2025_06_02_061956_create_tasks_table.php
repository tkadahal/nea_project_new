<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directorate_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained();

            $table->foreignId('parent_id')->nullable()->constrained('tasks')->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();

            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();

            $table->foreignId('status_id')->index()->constrained();
            $table->foreignId('priority_id')->index()->constrained();

            $table->boolean('active')->default(0);

            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['deleted_at', 'directorate_id']);
        });
    }
};
