<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->integer('lmbis_id')->nullable();

            $table->foreignId('directorate_id')->index()->constrained();
            $table->foreignId('department_id')->nullable()->index()->constrained();
            $table->foreignId('budget_heading_id')->nullable()->index()->constrained();
            $table->foreignId('status_id')->index()->constrained();
            $table->foreignId('priority_id')->index()->constrained();

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('location')->nullable();

            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();

            $table->decimal('progress', 8, 2)->nullable();

            $table->foreignId('project_manager')->nullable()->index()->constrained('users');

            $table->boolean('active')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['deleted_at']);
            $table->unique(['budget_heading_id', 'lmbis_id']);
        });
    }
};
