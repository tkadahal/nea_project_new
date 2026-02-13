<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activity_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('project_activity_schedules')->onDelete('cascade');
            $table->decimal('weightage', 5, 2)->nullable();
            $table->enum('project_type', ['transmission_line', 'substation', 'generation']);
            $table->integer('level')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['project_type', 'level']);
            $table->index('parent_id');

            $table->unique(['code', 'project_type']);
        });
    }
};
