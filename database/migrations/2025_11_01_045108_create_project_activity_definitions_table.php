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
            $table->string('program')->index();
            $table->integer('expenditure_id'); // can be only two: 1 for capital, 2 for recurrent
            $table->text('description')->nullable();
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->foreignId('parent_id')->nullable()->constrained('project_activity_definitions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['project_id', 'program']);
            $table->index('parent_id');
        });
    }
};
