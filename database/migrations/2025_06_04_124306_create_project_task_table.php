<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained();
            $table->foreignId('task_id')->constrained();
            $table->foreignId('status_id')->constrained()->index();
            $table->string('progress')->nullable();
            $table->primary(['project_id', 'task_id']);
            $table->timestamps();
        });
    }
};
