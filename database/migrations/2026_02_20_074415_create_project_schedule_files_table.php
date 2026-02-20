<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedule_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('project_activity_schedules')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'schedule_id']);
            $table->index('file_type');
        });
    }
};
