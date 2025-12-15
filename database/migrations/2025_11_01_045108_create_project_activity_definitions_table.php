<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activity_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('sort_index', 50)->index();
            $table->tinyInteger('depth')->default(0);
            $table->string('program')->nullable()->index();
            $table->integer('expenditure_id'); // can be only two: 1 for capital, 2 for recurrent
            $table->text('description')->nullable();
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('project_activity_definitions')->onDelete('cascade');
            $table->enum('status', ['draft', 'under_review', 'approved'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'program']);
            $table->index('parent_id');
        });
    }
};
