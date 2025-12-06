<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->string('title');
            $table->boolean('completed')->default(false);
            $table->foreignId('status_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'deleted_at']);
        });
    }
};
