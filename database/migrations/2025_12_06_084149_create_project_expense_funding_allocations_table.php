<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expense_funding_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_expense_quarter_id')->constrained()->onDelete('cascade');
            $table->enum('funding_source', ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy']);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_expense_quarter_id', 'funding_source']);
        });
    }
};
