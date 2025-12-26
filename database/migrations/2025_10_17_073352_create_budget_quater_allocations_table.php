<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_quater_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->string('quarter', 10);

            $table->decimal('internal_budget', 18, 2)->default(0);
            $table->decimal('government_share', 18, 2)->default(0);
            $table->decimal('government_loan', 18, 2)->default(0);
            $table->decimal('foreign_loan', 18, 2)->default(0);
            $table->decimal('foreign_subsidy', 18, 2)->default(0);
            $table->decimal('total_budget', 18, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }
};
