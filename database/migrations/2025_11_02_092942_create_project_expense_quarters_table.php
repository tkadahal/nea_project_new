<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expense_quarters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_expense_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('quarter')->unsigned()->comment('1-4');
            $table->decimal('quantity', 18, 2)->default(0.00);
            $table->decimal('amount', 18, 2)->default(0.00);
            $table->enum('status', ['draft', 'finalized'])->default('draft')->index()
                ->comment('Quarter expense status: draft until funding allocation is completed');
            $table->timestamps();
            $table->softDeletes();

            // Constraints and indexes
            $table->unique(['project_expense_id', 'quarter']);
            $table->index('quarter');
        });
    }
};
